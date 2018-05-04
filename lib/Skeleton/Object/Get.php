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
	 * @access public
	 * @return array objects
	 */
	public static function get_all($sort = null, $direction = null) {
		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		$where = '';
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['soft_delete']) AND self::$class_configuration['soft_delete'] === TRUE) {
			$where = ' AND archived IS NULL';
		}

		if (is_null($sort)) {
			$ids = $db->get_column('SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE 1' . $where, []);
		} else {
			if (is_null($direction)) {
				$direction = 'ASC';
			}
			$ids = $db->get_column('SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE 1' . $where . ' ORDER BY ' . $sort . ' ' . $direction);
		}

		$objects = [];
		foreach ($ids as $id) {
			$objects[] = self::get_by_id($id);
		}

		return $objects;
	}
}
