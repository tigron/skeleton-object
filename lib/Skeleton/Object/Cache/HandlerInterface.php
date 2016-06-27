<?php
/**
 *
 */

namespace Skeleton\Object\Cache;

interface HandlerInterface {

	public static function set($key, $value);
	public static function get($key);
	public static function delete($key);

}
