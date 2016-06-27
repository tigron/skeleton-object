<?php
/**
 * Config class
 * Configuration for Skeleton\Object
 *
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Object;

class Config {

	/**
	 * Auto update the slug
	 *
	 * This flag will be used to allow auto updating the slug
	 *
	 * @access public
	 * @var bool $auto_update_slug
	 */
	public static $auto_update_slug = false;

	/**
	 * Caching backend handler
	 *
	 * @access public
	 * @var string $classname
	 */
	public static $cache_handler = false;

	/**
	 * Caching backend config
	 *
	 * @access public
	 * @var string $classname
	 */
	public static $cache_handler_config = [];

}
