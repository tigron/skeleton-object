<?php
/**
 * trait: Child
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

use Skeleton\Database\Database;

trait Child {
	/**
	 * Child details
	 *
	 * @var array $child_details
	 * @access protected
	 */
	protected $child_details = [];

	/**
	 * Get the child details of this object
	 *
	 * @access private
	 */
	protected function get_child_details() {
		$table = self::trait_get_child_database_table();

		if (!isset($this->id) OR $this->id === null) {
			throw new \Exception('Could not fetch ' . $table . ' data: id not set');
		}

		$db = self::trait_get_database();
		$child_details = $db->get_row('SELECT * FROM ' . $db->quote_identifier($table) . ' WHERE ' . self::trait_get_parent_table_field_id() . '=?', [$this->id]);

		if ($child_details === null) {
			throw new \Exception('Could not fetch ' . $table . ' data: none found with id ' . $this->id);
		}

		$this->child_details = $child_details;
		$this->reset_dirty_fields();
	}

	/**
	 * Save
	 *
	 * @access public
	 */
	protected function trait_child_save() {
		$db = self::trait_get_database();
		$table = self::trait_get_child_database_table();

		$count = $db->get_one('SELECT count(*) FROM ' . $db->quote_identifier($table) . ' WHERE ' . self::trait_get_parent_table_field_id() . '=?', [$this->id]);

		$this->child_details[self::trait_get_parent_table_field_id()] = $this->id;
		if ($count == 0) {
			$db->insert($table, $this->child_details);
		} else {
			$where = self::trait_get_parent_table_field_id() . '=' . $db->quote($this->id);
			$db->update($table, $this->child_details, $where);
		}
	}

	/**
	 * trait_get_database_table: finds out which table we need to use
	 *
	 * @access private
	 * @return string $table
	 */
	public static function trait_get_child_database_table() {
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['database_table'])) {
			return self::$class_configuration['database_table'];
		} else {
			return strtolower((new \ReflectionClass(get_class()))->getShortName());
		}
	}

	/**
	 * trait_get_table_field_id: get the field that is used as ID
	 *
	 * @access private
	 * @return string $id
	 */
	public static function trait_get_parent_table_field_id() {
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['table_field_id'])) {
			return self::$class_configuration['parent_field_id'];
		} else {
			return strtolower((new \ReflectionClass(get_class()))->getParentClass()->getShortName() . '_id');
		}
	}
}
