<?php
/**
 * trait: Cache
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object;

trait Cache {

	/**
	 * Get
	 *
	 * @access public
	 * @param string $classname
	 * @param int $id
	 */
	public static function cache_get($key) {
		if (Config::$cache_handler === false) {
			throw new \Exception('Caching not enabled');
		}

		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		return $handler::get($key);
	}

	/**
	 * multi Get
	 *
	 * @access public
	 * @param string $classname
	 * @param array $keys
	 */
	public static function cache_multi_get($keys) {
		if (Config::$cache_handler === false) {
			throw new \Exception('Caching not enabled');
		}

		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		return $handler::multi_get($keys);
	}

	/**
	 * Add
	 *
	 * @access	public
	 * @param Object $object
	 */
	public static function cache_set($key, $object) {
		if (Config::$cache_handler === false) {
			throw new \Exception('Caching not enabled');
		}

		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		$handler::set($key, $object);
	}

	/**
	 * Delete
	 *
	 * @access public
	 * @param Object $object
	 */
	public static function cache_delete($key) {
		if (Config::$cache_handler === false) {
			throw new \Exception('Caching not enabled');
		}

		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		$handler::delete($key);
	}

	/**
	 * Key of the invalidation guard of an object
	 *
	 * The guard lives next to the cached object and is replaced on every
	 * invalidation. It is never a cached object itself, so it can not collide
	 * with a cache key.
	 *
	 * @access public
	 * @param int $id
	 * @return string $key
	 */
	public static function cache_guard_key($id) {
		return get_called_class()::trait_get_cache_prefix() . '_guard_' . $id;
	}

	/**
	 * Read the invalidation guard of an object
	 *
	 * @access public
	 * @param int $id
	 * @return mixed $guard null when the object was never invalidated
	 */
	public static function cache_guard_get($id) {
		if (Config::$cache_handler === false) {
			return null;
		}

		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;

		try {
			return $handler::get(get_called_class()::cache_guard_key($id));
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Mark an object as invalidated
	 *
	 * Any read that started before this call is no longer allowed to publish
	 * the snapshot it loaded: it may predate the write that is being made now.
	 *
	 * @access public
	 * @param int $id
	 */
	public static function cache_guard_bump($id) {
		if (Config::$cache_handler === false) {
			return;
		}

		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		$handler::set(get_called_class()::cache_guard_key($id), uniqid('', true));
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public static function cache_flush() {
		if (Config::$cache_handler === false) {
			throw new \Exception('Caching not enabled');
		}

		$handler = '\Skeleton\Object\Cache\Handler\\' . Config::$cache_handler;
		$handler::flush();
	}

}
