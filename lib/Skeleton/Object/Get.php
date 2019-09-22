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
	 * @parm int $id
	 * @return object
	 */
	public static function get_by_id($id) {
		if ($id === null) {
			throw new \Exception('Can not fetch ' . get_called_class() . ' with id null');
		}

		if (self::trait_cache_enabled()) {
			try {
				$object = self::cache_get(get_class() . '_' . $id);
				return $object;
			} catch (\Exception $e) {	}
		}

		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['child_classname_field'])) {
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
			self::cache_set(get_class() . '_' . $id, $object);
		}
		return $object;
	}

	/**
	 * Get all
	 *
	 * @param string|null $sort
	 * @param string|null $direction
	 * @param string|null $indexBy
	 *
	 * @access public
	 * @return array objects
	 *
	 * @throws \Exception
	 */
	public static function get_all($sort = null, $direction = null, $indexBy = null) {
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

		if (is_null($sort)) {
			$ids = $db->get_column('SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE 1=1' . $where, []);
		} else {
			if (is_null($direction)) {
				$direction = 'ASC';
			}
			$ids = $db->get_column('SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE 1=1' . $where . ' ORDER BY ' . $sort . ' ' . $direction);
		}

		$objects = [];
		foreach ($ids as $id) {
			$object = self::get_by_id($id);
			if($indexBy !== null) {
				$objects[$object->{$indexBy}] = $object;
			} else {
				$objects[] = $object;
			}
		}

		return $objects;
	}
}
