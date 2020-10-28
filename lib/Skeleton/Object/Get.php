<?php
/**
 * trait: Get
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

trait Get {
	/**
	 * Get object info
	 *
	 * @access public
	 * @return array $details
	 */
	public function get_info() {
		$info = [];
		if (isset($this->child_details)) {
			$info = array_merge($info, $this->child_details);
		}
		$info = array_merge($info, $this->details);
		return $info;
	}

	/**
	 * Get classname
	 *
	 * @access public
	 * @return string $classname
	 */
	public function get_classname() {
		return (new \ReflectionClass($this))->getShortName();
	}

	/**
	 * Get by id
	 *
	 * @access public
	 * @param int $id
	 * @return object
	 */
	public static function get_by_id($id) {
		if ($id === null) {
			throw new \Exception('Can not fetch ' . get_called_class() . ' with id null');
		}

		if (self::trait_cache_enabled()) {
			try {
				$object = self::cache_get(self::trait_get_cache_key($object));
				return $object;
			} catch (\Exception $e) {}
		}

		if (property_exists(get_class(), 'class_configuration') && isset(self::$class_configuration['child_classname_field'])) {
			$classname_field = self::$class_configuration['child_classname_field'];
			$table = self::trait_get_database_table();
			$db = self::trait_get_database();
			$classname = $db->get_one('SELECT ' . $classname_field . ' FROM ' . $db->quote_identifier($table) . ' WHERE id=?', [ $id ]);
			if ($classname === null) {
				throw new \Exception('Can not fetch ' . get_called_class() . ' with id ' . $id);
			}
		} else {
			$classname = get_called_class();
		}

		$object = new $classname($id);
		if (self::trait_cache_enabled()) {
			self::cache_set(self::trait_get_cache_key($object), $object);
		}

		return $object;
	}

	/**
	 * Get all
	 *
	 * @access public
	 * @return array objects
	 */
	public static function get_all($sort = null, $direction = null) {
		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		$where = '';
		$field_archived = self::trait_get_table_field_archived();
		foreach (self::get_object_fields() as $field) {
			if ($field['Field'] != $field_archived) {
				continue;
			}

			$where = ' AND ' . $field_archived . ' IS NULL';
		}

		$query = 'SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE 1=1' . $where;
		if ($sort !== null) {
			if ($direction === null) {
				$direction = 'ASC';
			}

			$query .= ' ORDER BY ' . $sort . ' ' . $direction;
		}

		$ids = $db->get_column($query, []);

		$objects = [];
		foreach ($ids as $id) {
			$objects[] = self::get_by_id($id);
		}

		return $objects;
	}
}
