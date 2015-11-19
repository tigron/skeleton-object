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
			throw new \Exception('It seems you don\'t want to delete this object, please use \"archive()\" instead');
		}

		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		if (isset(self::$object_text_fields)) {
			$object_texts = Text::get_by_object($this);
			foreach ($object_texts as $object_text) {
				$object_text->delete();
			}
		}

		$db->query('DELETE FROM ' . $table . ' WHERE ' . self::trait_get_table_field_id() . '=?', [$this->id]);
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
		$this->archived = '0000-00-00 00:00:00';
		$this->save();
	}
}
