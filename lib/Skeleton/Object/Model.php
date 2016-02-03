<?php
/**
 * trait: Model
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

use Skeleton\Database\Database;

trait Model {
	/**
	 * @var int $id
	 * @access public
	 */
	public $id;

	/**
	 * Details
	 *
	 * @var array $details
	 * @access private
	 */
	protected $details = [];

	/**
	 * Dirty fields
	 * Unsaved fields
	 *
	 * @var array $dirty_fields
	 * @access private
	 */
	private $dirty_fields = [];

	/**
	 * Object text cache
	 *
	 * @access private
	 * @var array $object_text_cache
	 */
	private $object_text_cache = [];

	/**
	 * Object text update
	 *
	 * @access private
	 * @var array $object_text_updated
	 */
	private $object_text_updated = [];

	/**
	 * Constructor
	 *
	 * @access public
	 * @param int $id
	 */
	public function __construct($id = null) {
		if ($id !== null) {
			$this->id = $id;
			$this->get_details();
		}
	}

	/**
	 * Get the details of this object
	 *
	 * @access private
	 */
	protected function get_details() {
		$table = self::trait_get_database_table();

		if (!isset($this->id) OR $this->id === null) {
			throw new \Exception('Could not fetch ' . $table . ' data: id not set');
		}

		$db = self::trait_get_database();
		$details = $db->get_row('SELECT * FROM ' . $db->quote_identifier($table) . ' WHERE ' . self::trait_get_table_field_id() . '=?', [$this->id]);

		if ($details === null) {
			throw new \Exception('Could not fetch ' . $table . ' data: none found with id ' . $this->id);
		}

		$this->details = $details;
		$this->reset_dirty_fields();
	}

	/**
	 * Set a detail
	 *
	 * @access public
	 * @param string $key
	 * @param mixex $value
	 */
	public function __set($key, $value) {
		// Check if the key we want to set exists in the disallow_set variable
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['disallow_set'])) {
			if (is_array(self::$class_configuration['disallow_set'])) {
				if (in_array($key, self::$class_configuration['disallow_set'])) {
					throw new \Exception('Can not set ' . $key . ' directly');
				}
			} else {
				throw new \Exception('Improper use of disallow_set');
			}
		}

		if (is_object($value) AND property_exists($value, 'id')) {
			$key = $key . '_id';
			$this->$key = $value->id;
			return;
		}

		if (isset(self::$object_text_fields)) {
			if (strpos($key, 'text_') === 0) {
				$this->trait_set_object_text($key, $value);
				return;
			}
		}

		if (array_key_exists($key, $this->details) AND $this->details[$key] != $value) {
			// A new value is set, let's tag it as dirty

			if (!isset($this->dirty_fields[$key])) {
				$this->dirty_fields[$key] = $this->details[$key];
			}
		}

		$this->details[$key] = $value;
	}

	/**
	 * set an object text
	 *
	 * @access private
	 * @param string $key
	 * @param string $value
	 */
	private function trait_set_object_text($key, $value) {
		list($language, $label) = explode('_', str_replace('text_', '', $key), 2);

		if (!in_array($label, self::$object_text_fields)) {
			throw new \Exception('Incorrect text field:' . $label);
		}

		if (!isset($this->object_text_cache[$key])) {
			$this->trait_get_object_text($key);
		}

		if ($this->object_text_cache[$key] != $value) {
			$this->object_text_cache[$key] = $value;
			$this->object_text_updated[$key] = $value;
		}
	}

	/**
	 * set an object text
	 *
	 * @access private
	 * @param string $key
	 * @param string $value
	 */
	private function trait_get_object_text($key) {
		list($language, $label) = explode('_', str_replace('text_', '', $key), 2);

		if (!in_array($label, self::$object_text_fields)) {
			throw new \Exception('Incorrect text field:' . $label);
		}

		if ($this->id === null) {
			$this->object_text_cache[$key] = '';
		}

		if (!isset($this->object_text_cache[$key])) {
			$language = \Skeleton\I18n\Language::get_by_name_short($language);
			$this->object_text_cache[$key] = Text::get_by_object_label_language($this, $label, $language)->content;
		}

		return $this->object_text_cache[$key];
	}

	/**
	 * Get a detail
	 *
	 * @access public
	 * @param string $key
	 * @return mixed $value
	 */
	public function __get($key) {
		if (isset($this->details[strtolower($key) . '_id']) AND class_exists($key)) {
			return $key::get_by_id($this->details[strtolower($key) . '_id']);
		}

		if (array_key_exists($key, $this->details)) {
			return $this->details[$key];
		}

		if (isset(self::$object_text_fields)) {
			if (strpos($key, 'text_') === 0) {
				return $this->trait_get_object_text($key);
			}
		}

		throw new \Exception('Unknown key requested: ' . $key);
	}

	/**
	 * Isset
	 *
	 * @access public
	 * @param string $key
	 * @return bool $isset
	 */
	public function __isset($key) {
		if (isset($this->details[strtolower($key) . '_id']) AND class_exists($key)) {
			return true;
		}

		if (array_key_exists($key, $this->details)) {
			return true;
		}

		if (isset(self::$object_text_fields)) {
			if (strpos($key, 'text_') === 0) {
				list($language, $label) = explode('_',  str_replace('text_', '', $key), 2);

				if (!in_array($label, self::$object_text_fields)) {
					return false;
				} else {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Is Dirty
	 *
	 * @access public
	 * @param string $key
	 * @return bool $dirty
	 */
	public function is_dirty($key = null) {
		$dirty_fields = $this->get_dirty_fields();
		if (count($dirty_fields) == 0) {
			return false;
		}

		if (!is_null($key) AND !isset($dirty_fields[$key])) {
			return false;
		}

		return true;
	}

	/**
	 * Get dirty fields
	 *
	 * @access public
	 * @return array $dirty_fields
	 */
	public function get_dirty_fields() {
		return array_merge($this->dirty_fields, $this->object_text_updated);
	}

	/**
	 * Reset dirty fields
	 *
	 * @access public
	 */
	public function reset_dirty_fields() {
		$this->dirty_fields = [];
		$this->object_text_updated = [];
		$this->object_text_cache = [];
	}

	/**
	 * Load array
	 *
	 * @access public
	 * @param array $details
	 */
	public function load_array($details) {
		foreach ($details as $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * trait_get_database_config_name: finds out which database name we need to get
	 *
	 * @access private
	 * @return Database $database
	 */
	private static function trait_get_database() {
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['database_config_name'])) {
			$db = Database::get(self::$class_configuration['database_config_name']);
		} else {
			$db = Database::get();
		}
		return $db;
	}

	/**
	 * trait_get_database_table: finds out which table we need to use
	 *
	 * @access private
	 * @return string $table
	 */
	private static function trait_get_database_table() {
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
	private static function trait_get_table_field_id() {
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['table_field_id'])) {
			return self::$class_configuration['table_field_id'];
		} else {
			return 'id';
		}
	}

	/**
	 * trait_get_table_field_created: get the field that is used for 'created'
	 *
	 * @access private
	 * @return string $created
	 */
	private static function trait_get_table_field_created() {
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['table_field_created'])) {
			return self::$class_configuration['table_field_created'];
		} else {
			return 'created';
		}
	}

	/**
	 * trait_get_table_field_updated: get the field that is used for 'updated'
	 *
	 * @access private
	 * @return string $updated
	 */
	private static function trait_get_table_field_updated() {
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['table_field_updated'])) {
			return self::$class_configuration['table_field_updated'];
		} else {
			return 'updated';
		}
	}

	/**
	 * Trait_get_link_tables
	 *
	 * @access private
	 * @return array $tables
	 */
	private static function trait_get_link_tables() {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();
		$fields = $db->get_columns($table);
		$tables = $db->get_column('SHOW tables');

		$joins = [];
		foreach ($fields as $field) {
			if (substr($field, -3) != '_id') {
				continue;
			}

			$link_table = substr($field, 0, -3);

			if (in_array($link_table, $tables)) {
				$joins[] = $link_table;
			}
		}
		return $joins;
	}
}
