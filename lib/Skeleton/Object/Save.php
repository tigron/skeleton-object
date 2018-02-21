<?php
/**
 * trait: Save
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

class Exception_Validation extends \Exception {

	private $errors = [];

	public function __construct($errors) {
		$this->errors = $errors;
		$this->message = 'Validation error! The following fields contain errors: ' . implode(', ', array_keys($this->errors));		
	}

	public function get_errors() {
		return $this->errors;
	}

}

trait Save {

	/**
	 * Save the object
	 *
	 * @access public
	 */
	public function save($validate = true) {
		// If we have a validate() method, execute it
		if (method_exists($this, 'validate') AND is_callable([$this, 'validate']) and $validate) {
			if ($this->validate($errors) === false) {
				throw new Exception_Validation($errors);
			}
		}

		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		if (!isset($this->id) OR $this->id === null) {
			if (!isset($this->details[ self::trait_get_table_field_created()])) {
				$this->details[ self::trait_get_table_field_created() ] = date('Y-m-d H:i:s');
			}
		} else {
			$this->details[ self::trait_get_table_field_updated() ] = date('Y-m-d H:i:s');
		}

		if (method_exists($this, 'generate_slug') AND is_callable([$this, 'generate_slug']) AND (\Skeleton\Object\Config::$auto_update_slug === true OR !isset($this->details['slug']) OR $this->details['slug'] == '')) {
			$slug = $this->generate_slug();
			$this->details['slug'] = $slug;
		}

		if (!isset($this->id) OR $this->id === null) {
			$db->insert($table, $this->details);
			$this->id = $db->get_one('SELECT LAST_INSERT_ID();');
		} else {
			$where = self::trait_get_table_field_id() . '=' . $db->quote($this->id);
			$db->update($table, $this->details, $where);
		}

		foreach ($this->object_text_updated as $key => $value) {
			list($language, $label) = explode('_', str_replace('text_', '', $key), 2);
			$language_interface = \Skeleton\I18n\Config::$language_interface;
			$language = $language_interface::get_by_name_short($language);
			$object_text = \Skeleton\I18n\Object\Text::get_by_object_label_language($this, $label, $language);
			$object_text->content = $this->object_text_cache[$key];
			$object_text->save();

			if (method_exists(get_called_class(), 'cache_set')) {
				$key = get_called_class() . '_' . $object_text->object_id . '_' . $object_text->label . '_' . $language->name_short;
				self::cache_delete($key);
				self::cache_set($key, $object_text);
			}
		}

		if (method_exists($this, 'trait_child_save') and is_callable([$this, 'trait_child_save'])) {
			$this->trait_child_save();
		}

		$this->get_details();

		if (method_exists(get_called_class(), 'cache_set')) {
			self::cache_delete(get_called_class() . '_' . $this->id);
			self::cache_set(get_called_class() . '_' . $this->id, $this);
		}

	}
}
