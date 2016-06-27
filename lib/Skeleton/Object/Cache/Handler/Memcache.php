<?php
/**
 * trait: Cache
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object\Cache\Handler;

class Memcache implements \Skeleton\Object\Cache\HandlerInterface {

	private static $memcache = null;

	/**
	 * Get from objectcache
	 *
	 * @access public
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key) {
		$memcache = self::connect();
		$var = $memcache->get($key);
		if ($var === false) {
			throw new \Exception('Object not in cache');
		} else {
			return $var;
		}
	}

	/**
	 * Put
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($key, $value) {
		$memcache = self::connect();
		$config = \Skeleton\Object\Config::$cache_handler_config;
		$memcache->add($key, $value, false, $config['expire']);
	}

	/**
	 * Delete
	 *
	 * @access public
	 * @param string $key
	 */
	public static function delete($key) {
		$memcache = self::connect();
		$memcache->delete($key);
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public static function flush() {
		$memcache = self::connect();
		$memcache->flush();
	}

	/**
	 * Get the current memcache object
	 *
	 * @access public
	 * @return Memcache $memcache
	 */
	public static function connect() {
		if (self::$memcache === null) {
			$config = \Skeleton\Object\Config::$cache_handler_config;
			self::$memcache = new \Memcache();
			self::$memcache->connect($config['hostname'], $config['port']);
		}

		return self::$memcache;
	}

}
