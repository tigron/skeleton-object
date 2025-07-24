<?php
/**
 * trait: Cache
 *
 * @author Christophe Gosiau <christophe.gosiau@tigron.be>
 * @author Gerry Demaret <gerry.demaret@tigron.be>
 * @author David Vandemaele <david.vandemaele@tigron.be>
 */

namespace Skeleton\Object\Cache\Handler;

class Memory implements \Skeleton\Object\Cache\HandlerInterface {

	private static $memory = null;

	private $details = [];

	/**
	 * __get
	 *
	 * @access public
	 * @param string $key
	 * @return mixed $value
	 */
	public function __get($key) {
		if (!isset($this->details[$key])) {
			throw new \Exception('Unknown key ' . $key);
		}
		return $this->details[$key];
	}

	/**
	 * __set
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->details[$key] = $value;
	}

	/**
	 * __isset
	 *
	 * @access public
	 * @param string $key
	 */
	public function __isset($key) {
		if (isset($this->details[$key])) {
			return true;
		}
		return false;
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public function clear() {
		$this->details = [];
	}

	/**
	 * Get from objectcache
	 *
	 * @access public
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key) {
		$memory = self::fetch();
		if (isset($memory->$key)) {
			return $memory->$key;
		} else {
			throw new \Exception('Object not in cache');
		}
	}

	/**
	 * Get multi from objectcache
	 *
	 * @access public
	 * @param array $keys
	 * @return mixed
	 */
	public static function multi_get($keys) {
		$result = [];
		foreach ($keys as $key) {
			try {
				$result[] = self::get($key);
			} catch (\Exception $e) {}
		}
		return $result;
	}

	/**
	 * Put
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($key, $value) {
		$memory = self::fetch();
		$memory->$key = $value;
	}

	/**
	 * Delete
	 *
	 * @access public
	 * @param string $key
	 */
	public static function delete($key) {
		$memory = self::fetch();
		unset($memory->$key);
	}

	/**
	 * Flush
	 *
	 * @access public
	 */
	public static function flush() {
		$memory = self::fetch();
		$memory->clear();
	}

	/**
	 * Get the current memcache object
	 *
	 * @access public
	 * @return Memcache $memcache
	 */
	public static function fetch() {
		if (self::$memory === null) {
			self::$memory = new self();
		}

		return self::$memory;
	}

}
