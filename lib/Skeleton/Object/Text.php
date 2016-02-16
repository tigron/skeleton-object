<?php
/**
 * Object_Text class
 *
 * FIXME: this shouldn't be here at all!
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

 namespace Skeleton\Object;
 use Skeleton\Database\Database;

class Text {
	use Model, Save, Get, Delete;

	/**
	 * class_configuration
	 *
	 * @access private
	 */
	private static $class_configuration = [
		'database_table' => 'object_text'
	];

	/**
	 * Set a detail
	 *
	 * @access public
	 * @param string $key
	 * @param mixex $value
	 */
	public function __set($key, $value) {
		if ($key == 'object') {
			$this->details['classname'] = get_class($value);
			if ($value->id === null) {
				$value->save();
			}
			$this->details['object_id'] = $value->id;
		} else {
			$this->details[$key] = $value;
		}
	}

	/**
	 * Get a detail
	 *
	 * @access public
	 * @param string $key
	 * @return mixed $value
	 */
	public function __get($key) {
		if ($key == 'language') {
			return \Skeleton\I18n\Language::get_by_id($this->language_id);
		} elseif (!isset($this->details[$key])) {
			throw new Exception('Unknown key requested: ' . $key);
		} else {
			return $this->details[$key];
		}
	}

	/**
	 * Get translation
	 *
	 * @access public
	 * @param Language $language
	 */
	public function get_translation(\Skeleton\I18n\Language $language) {
		$classname = $this->classname;
		$class = $classname::get_by_id($this->object_id);
		$label = 'text_' . $language->name_short . '_' . $this->label;
		return $class->$label;
	}

	/**
	 * Get by object
	 *
	 * @access public
	 * @param mixed $object
	 */
	public static function get_by_object($object) {
		$db = Database::Get();
		$class = get_class($object);
		$data = $db->get_all('SELECT id FROM object_text WHERE classname=? AND object_id=?', array($class, $object->id));
		$object_texts = array();
		foreach ($data as $details) {
			$object_text = new self();
			$object_text->id = $details['id'];
			$object_text->details = $details;

			$object_texts[] = $object_text;
		}
		return $object_texts;
	}

	/**
	 * Get by object, label, language
	 *
	 * @access public
	 * @param mixed $object
	 * @param string $label
	 * @param Language $language
	 */
	public static function get_by_object_label_language($object, $label, \Skeleton\I18n\LanguageInterface $language, $auto_create = true) {
		$db = Database::Get();
		$class = get_class($object);
		$data = $db->get_row('SELECT * FROM object_text WHERE classname=? AND object_id=? AND label=? AND language_id=?', array($class, $object->id, $label, $language->id));

		if ($data === null) {
			if (!$auto_create) {
				throw new \Exception('Object text does not exists');
			} else {
				$requested = new self();
				$requested->object = $object;
				$requested->language_id = $language->id;
				$requested->label = $label;
				$requested->content = '';
				$requested->save();
				return $requested;
			}
		}

		$object_text = new self();
		$object_text->id = $data['id'];
		$object_text->details = $data;

		return $object_text;
	}

	/**
	 * Get for object classname
	 *
	 * @access public
	 * @param string $classname
	 * @return array $object_texts
	 */
	public static function get_by_object_classname($classname) {
		$db = Database::Get();
		$ids = $db->getCol('SELECT id FROM object_text WHERE classname=?', array($classname));
		$object_texts = array();
		foreach ($ids as $id) {
			$object_texts[] = self::get_by_id($id);
		}
		return $object_texts;
	}

	/**
	 * Get for object classname
	 *
	 * @access public
	 * @param string $classname
	 * @return array $object_texts
	 */
	public static function get_by_classname_language($classname, \Skeleton\I18n\Language $language) {
		$db = Database::Get();
		$ids = $db->getCol('SELECT id FROM object_text WHERE classname=? AND language_id=?', array($classname, $language->id));
		$object_texts = array();
		foreach ($ids as $id) {
			$object_texts[] = self::get_by_id($id);
		}
		return $object_texts;
	}

	/**
	 * Get classnames
	 *
	 * @access public
	 * @return array $classnames
	 */
	public static function get_classnames() {
		$db = Database::Get();
		$classnames = $db->get_column('SELECT DISTINCT(classname) FROM object_text', array());
		return $classnames;
	}
}
