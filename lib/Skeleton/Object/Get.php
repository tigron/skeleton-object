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
	 * Get classname
	 *
	 * @access public
	 * @return string $classname
	 */
	public function get_classname() {
		return (new \ReflectionClass($this))->getShortName();
	}


	/**
	 * Get by ids
	 *
	 * @access public
	 * @param array $ids
	 * @return array $obecjts
	 */
	public static function get_by_ids($ids) {
		// Preserve the order of the ids
		$result = array_fill_keys($ids, null);

		$uncached_object_ids = [];
		if (get_called_class()::trait_cache_enabled()) {
			$prefix = get_called_class()::trait_get_cache_prefix();
			$cache_keys = [];
			foreach ($ids as $id) {
				$cache_keys[$id] = $prefix . '_' . $id;
			}
			$cached_objects = get_called_class()::cache_multi_get($cache_keys);

			// we use it to avoid repeatedly iterating if some cache is missing
			$cached_objects_map = [];

			foreach ($cached_objects as $cached_object) {
				$cached_objects_map[$cached_object->id] = $cached_object;
				unset($cache_keys[$cached_object->id]);
			}

			if (count($cache_keys) === 0) {
				// we should reset indexes in case if someone tries to get first element via 0 index
				return array_values($cached_objects);
			}

			foreach ($ids as $id) {
				if (isset($cached_objects_map[$id]) === false) {
					$uncached_object_ids[] = $id;
					continue;
				}
				$result[$id] = $cached_objects_map[$id];
			}
		} else {
			$uncached_object_ids = $ids;
		}

		// check if we have any uncached objects
		if (count($uncached_object_ids) === 0) {
			// we should reset indexes in case if someone tries to get first element via 0 index
			return array_values($result);
		}

		/**
		 * If in class_configuration a child_classname field is specified,
		 * use this
		 */
		if (property_exists(get_class(), 'class_configuration') === true
			&& isset(get_called_class()::$class_configuration['child_classname_field']) === true
		) {
			$classname_field = get_called_class()::$class_configuration['child_classname_field'];
		}

		$db = get_called_class()::trait_get_database();

		$table = get_called_class()::trait_get_database_table();
		$table_field_id = get_called_class()::trait_get_table_field_id();

		$grouped_child_ids = [];
		$uncached_objects = [];
		// fetch uncached objects
		foreach (array_chunk($uncached_object_ids, Config::$chunk_size) as $ids) {
			$rows = $db->get_all(
				'SELECT * FROM ' . $db->quote_identifier($table) . ' WHERE ' . $table_field_id . ' IN ('
					. implode(',', array_fill(0, count($ids), '?')) .
				') ',
				$ids
			);

			foreach ($rows as $row) {
				// get object classname
				$classname = get_called_class();
				if (isset($classname_field) === true && class_exists($row[$classname_field]) === true) {
					$classname = $row[$classname_field];
				}

				// if class is abstract we skip current iteration, we update it later via get_by_id
				if ((new \ReflectionClass($classname))->isAbstract() === true) {
					continue;
				}

				// set object details
				$object = new $classname();
				$object->id = $row[get_called_class()::trait_get_table_field_id()];
				$object->details = $row;

				// update uncached objects
				$uncached_objects[$object->id] = $object;

				// check if child details are available
				if (isset($classname_field) === false
					|| method_exists($object, 'trait_get_child_details') === false
					|| is_callable([ $object, 'trait_get_child_details' ]) === false
				) {
					continue;
				}

				$child_classname = $row[$classname_field];
				if ($child_classname::trait_get_child_database_table() === null) {
					continue;
				}

				if (isset($grouped_child_ids[$child_classname]) === false) {
					$grouped_child_ids[$child_classname] = [];
				}

				$grouped_child_ids[$child_classname][] = $object->id;
			}
		}

		// fetch child details
		foreach ($grouped_child_ids as $child_classname => $parent_ids) {
			$table = $child_classname::trait_get_child_database_table();
			$table_field_id = $child_classname::trait_get_parent_table_field_id();

			foreach (array_chunk($parent_ids, Config::$chunk_size) as $ids) {
				$rows = $db->get_all(
					'SELECT * FROM ' . $db->quote_identifier($table) . ' WHERE ' . $table_field_id . ' IN ('
						. implode(',', array_fill(0, count($ids), '?')) .
					') ',
					$ids
				);

				foreach ($rows as $row) {
					// get parent id
					$id = $row[$child_classname::trait_get_parent_table_field_id()];
					if (isset($uncached_objects[$id]) === false) {
						throw new \Exception('Could not fetch ' . $table . ' data: none found with id ' . $id);
					}

					// set child details
					$object = $uncached_objects[$id];
					$object->child_details = $row;
					$object->reset_dirty_fields();

					// update uncached objects with child details
					$uncached_objects[$id] = $object;
				}
			}
		}

		// Fill in the result with values that could not be obtained from cache
		foreach ($result as $id => $value) {
			if ($value !== null) {
				continue;
			}

			// if object not in static cache, fetch it
			if (isset($uncached_objects[$id]) === false) {
				$result[$id] = self::get_by_id($id);
				continue;
			}

			$object = $uncached_objects[$id];
			$result[$id] = $object;

			// cache object if enabled
			if (get_called_class()::trait_cache_enabled()) {
				get_called_class()::cache_set(get_called_class()::trait_get_cache_key($object), $object);
			}
		}

		// we should reset indexes in case if someone tries to get first element via 0 index
		return array_values($result);
	}

	/**
	 * Get by id
	 *
	 * @access public
	 * @param int $id
	 * @return object
	 */
	public static function get_by_id($id) {
		if ($id === null) {
			throw new \Exception('Can not fetch ' . get_called_class() . ' with id null');
		}

		if (get_called_class()::trait_cache_enabled()) {
			$prefix = get_called_class()::trait_get_cache_prefix();
			try {
				$object = get_called_class()::cache_get($prefix . '_' . $id);
				return $object;
			} catch (\Exception $e) { }
		}

		$classname = get_called_class();

		/**
		 * If in class_configuration a child_classname field is specified,
		 * use this
		 */
		if (property_exists(get_class(), 'class_configuration') && isset(self::$class_configuration['child_classname_field'])) {
			$classname_field = self::$class_configuration['child_classname_field'];
			$table = self::trait_get_database_table();
			$db = self::trait_get_database();
			$classname = $db->get_one('SELECT ' . $classname_field . ' FROM ' . $db->quote_identifier($table) . ' WHERE id=?', [ $id ]);
			if ($classname === null) {
				throw new \Exception('Can not fetch ' . get_called_class() . ' with id ' . $id);
			}
		}

		$object = new $classname($id);
		if (get_called_class()::trait_cache_enabled()) {
			get_called_class()::cache_set(get_called_class()::trait_get_cache_key($object), $object);
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
		$field_archived = self::trait_get_table_field_archived();
		foreach (self::get_object_fields() as $field) {
			if ($field['Field'] != $field_archived) {
				continue;
			}

			$where = ' AND ' . $field_archived . ' IS NULL';
		}

		$reflection = new \ReflectionClass(get_called_class());
		$traits = $reflection->getTraits();
		if (isset($traits['Skeleton\Object\Child']) === true) {
			$where .= ' AND classname = ' . $db->quote(get_called_class());
		}

		$query = 'SELECT ' . self::trait_get_table_field_id() . ' FROM ' . $db->quote_identifier($table) . ' WHERE 1=1' . $where;
		if ($sort !== null) {
			if ($direction === null) {
				$direction = 'ASC';
			}

			$query .= ' ORDER BY ' . $sort . ' ' . $direction;
		}

		$ids = $db->get_column($query, []);
		return self::get_by_ids($ids);
	}
}
