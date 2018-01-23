<?php
/**
 * trait: Save
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object;

use Cocur\Slugify\Slugify;
use Tigron\Skeleton\I18n\Language;

trait Slug {

	/**
	 * Generate a slug
	 *
	 * @access private
	 */
	private function generate_slug($append = 0, $unique = true) {
		$sluggable_field = 'name'; // default
		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['sluggable'])) {
			$sluggable_field = self::$class_configuration['sluggable'];
		}

		if (isset($this->details[$sluggable_field])) {
			$name = $this->details[$sluggable_field];
		} elseif (isset(self::$object_text_fields) AND in_array($sluggable_field, self::$object_text_fields)) {
			$sluggable_field = 'text_' . \Skeleton\I18n\Config::$base_language . '_' . $sluggable_field;
			if (isset($this->$sluggable_field) AND $this->$sluggable_field != '') {
				$name = $this->$sluggable_field;
			} else {
				throw new \Exception('No base found to generate slug');
			}
		} else {
			throw new \Exception('No base found to generate slug');
		}

		if (isset($this->id) AND $this->is_dirty($sluggable_field) === false and !empty($this->details['slug'])) {
			return $this->details['slug'];
		}

		$slugify = new Slugify();
		$slug = $slugify->slugify($name);

		if ($unique === false) {
			return $slug;
		}

		if ($append != 0) {
			$slug .= '-' . $append;
		}

		$slug_exist = false;
		try {
			$object = self::get_by_slug($slug);
			if ($this->id === null) {
				$slug_exist = true;
			}

			if ($this->id != $object->id) {
				$slug_exist = true;
			}

		} catch (\Exception $e) {
			$slug_exist = false;
		}

		if ($slug_exist) {
			++$append;
			return $this->generate_slug($append);
		}
		return $slug;
	}

	/**
	 * get by slug
	 *
	 * @access public
	 * @param string $name
	 * @return Object $object
	 */
	public static function get_by_slug($slug) {
		$table = self::trait_get_database_table();
		$db = self::trait_get_database();

		$id = $db->get_one('SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE slug=?', [$slug]);
		if ($id === null) {
			throw new \Exception('Object not found');
		}
		return self::get_by_id($id);
	}
}
