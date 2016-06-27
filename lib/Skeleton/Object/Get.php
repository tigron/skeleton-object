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
		return $this->details;
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

		if (method_exists(get_called_class(), 'cache_get')) {
			try {
				$object = self::cache_get(get_called_class() . '_' . $id);
				return $object;
			} catch (\Exception $e) {
				$classname = get_called_class();
				$object = new $classname($id);
				self::cache_set($classname . '_' . $object->id, $object);
				return $object;
			}
		} else {
			$classname = get_called_class();
			$object = new $classname($id);
			return $object;
		}
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
