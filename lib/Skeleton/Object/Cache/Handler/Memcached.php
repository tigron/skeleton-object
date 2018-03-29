<?php
/**
 * trait: Cache
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object\Cache\Handler;

class Memcached implements \Skeleton\Object\Cache\HandlerInterface {

	private static $memcached = null;

	/**
	 * Get from objectcache
	 *
	 * @access public
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key) {
		$memcached = self::connect();
		$var = $memcached->get($key);
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
		$memcached = self::connect();
		$config = \Skeleton\Object\Config::$cache_handler_config;
		$memcached->add($key, $value, $config['expire']);
	}

	/**
	 * Delete
	 *
	 * @access public
	 * @param string $key
	 */
	public static function delete($key) {
		$memcached = self::connect();
		$memcached->delete($key);
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public static function flush() {
		$memcached = self::connect();
		$memcached->flush();
	}

	/**
	 * Get the current memcached object
	 *
	 * @access public
	 * @return Memcache d$memcached
	 */
	public static function connect() {
		if (self::$memcached === null) {
			$config = \Skeleton\Object\Config::$cache_handler_config;
			self::$memcached = new \Memcached();
			self::$memcached->setOption(\Memcached::OPT_SERIALIZER, \Memcached::SERIALIZER_IGBINARY);
			self::$memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
			self::$memcached->setOption(\Memcached::OPT_NO_BLOCK, true);
			self::$memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
			self::$memcached->addServers([
				[$config['hostname'], $config['port']]
			]);
		}

		return self::$memcached;
	}

}
