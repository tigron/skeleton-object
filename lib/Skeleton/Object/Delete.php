<?php
/**
 * trait: Delete
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

trait Delete {

	/**
	 * Delete
	 *
	 * @access public
	 */
	public function delete() {
		/**
		 * This code is to prevent unwanted deletion
		 * It is temporary and should be removed in later versions
		 */
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['soft_delete']) AND self::$class_configuration['soft_delete'] === TRUE) {
			throw new \Exception('It seems you don\'t want to delete this object, please use "archive()" instead');
		}

		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		if (isset(self::$object_text_fields)) {
			$object_texts = \Skeleton\I18n\Object\Text::get_by_object($this);
			foreach ($object_texts as $object_text) {
				$object_text->delete();

				if (method_exists(get_called_class(), 'cache_delete')) {
					$key = get_called_class() . '_' . $object_text->object_id . '_' . $object_text->label . '_' . $object_text->language->name_short;
					self::cache_delete($key);
				}
			}
		}

		if (method_exists(get_called_class(), 'cache_delete')) {
			self::cache_delete(get_called_class() . '_' . $this->id);
		}

		$db->query('DELETE FROM ' . $db->quote_identifier($table) . ' WHERE ' . self::trait_get_table_field_id() . '=?', [$this->id]);
	}

	/**
	 * Archive
	 *
	 * @access public
	 */
	public function archive() {
		$this->archived = date('Y-m-d H:i:s');
		$this->save();
	}

	/**
	 * Restore
	 *
	 * @access public
	 */
	public function restore() {
		$this->archived = null;
		$this->save();
	}
}
