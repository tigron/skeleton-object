<?php
/**
 * trait: Slug
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object;

use Tigron\Skeleton\I18n\Language;

trait Slug {

	/**
	 * Generate a slug
	 *
	 * @access private
	 * @param bool $unique
	 * @return string $slug
	 */
	private function generate_slug($unique = true) {
		$sluggable_field = 'name'; // default

		if (property_exists(get_class(), 'class_configuration') AND isset(self::$class_configuration['sluggable'])) {
			$sluggable_field = self::$class_configuration['sluggable'];
		}

		if (isset($this->details[$sluggable_field])) {
			$name = $this->details[$sluggable_field];
		} elseif (isset(self::$object_text_fields) AND in_array($sluggable_field, self::$object_text_fields)) {
			$language_interface = \Skeleton\I18n\Config::$language_interface;
			$base_language = $language_interface::get_base();
			$sluggable_field = 'text_' . $base_language->name_short . '_' . $sluggable_field;
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

		// "Any-Latin": transliterate to latin while preserving what we can
		// "NFD; [:Nonspacing Mark:] Remove; NFC": move accents into separate characters, remove the accents
		// "Lower()": lowercase the end result
		$slug = transliterator_transliterate('Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; Lower()', $name);

		// "[:Punctuation:] Remove": replace any character in the unicode punctuation category with dashes
		$slug = preg_replace('/\p{P}/', '-', $slug);

		// Replace leftover non-alphanumerics with dashes
		$slug = preg_replace('/[^A-Za-z0-9 ]/', '-', $slug);

		// Replace spaces and consecutive dashes with single dashes
		$slug = preg_replace('/[-\s]+/', '-', $slug);

		// Remove any leading or trailing dashes
		$slug = trim($slug, '-');

		if ($unique === false) {
			return $slug;
		}

		while (true) {
			try {
				$object = self::get_by_slug($slug);

				if ($this->id === null || $this->id !== $object->id) {
					$slug = $slug . bin2hex(random_bytes(1));
				}
			} catch (\Exception $e) {
				// If the slug was not found, we're good to go
				break;
			}
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
