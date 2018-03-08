# skeleton-object

## Introduction

Functional traits to use in objects

The traits specified here take into account some special configuration
parameters that can be set within the class.

### disallow_set (array)

    private static $class_configuration = array (
      'disallow_set' => array (
        'directory',
        'full_path',
        'password',
      ),
    );

Prevents the direct setting of some class variables

###  database_table (string)

    private static $class_configuration = [
      'database_table' => 'my_super_special_class',
    ];

Overrides the default strtolower(get_class()) as tablename

###  database_config_name (string)

    private static $class_configuration = [
      'database_config_name' => 'database_dsn',
	];

Overrides the default database when using Database::get();

###  table_field_id (string)

	private static $class_configuration = [
	  'table_field_id' => 'my_strange_id',
	];

###  table_field_created (string)

	private static $class_configuration = [
	  'table_field_created' => 'my_strange_created_field',
	];

###  table_field_updated (string)

	private static $class_configuration = [
	  'table_field_updated' => 'my_strange_updated_field',
	];

###  sluggable (string)

	private static $class_configuration = [
	  'sluggable' => 'my_field_to_slug',
	];


## Traits

### Model trait

This trait provides basic functionality for an object in the database

    $object = new MyClass();
    $object->database_field_1 = $value1;
    $object->database_field_2 = $value2;

    $dirty_fields = $object->get_dirty_fields();

This variable now contains [ 'database_field_1', 'database_field_2' ]

    $object->load_array( $_POST['form_for_object'] );

### Delete trait

    $object->delete()

deletes the object permanently

    $object->archive()

stores the archive-date in field 'archived'

    $object->restore()

resets the field 'archived'

## Get trait

    $object->get_info()

returns an array containing all database fields of the object

    Object::get_by_id($id)

returns the object with id '$id'

    Object::get_all($sort, $direction)

returns all object.

Optional parameters:

    $sort: the field to sort on
    $direction: ASC/DESC

## Slug trait

When using the Slug trait, it is possible to auto update the slug when
saving the object (default is false).

    /**
     * Set the auto update slug flag
     */
    \Skeleton\Object\Config::$auto_update_slug = true;

## Child trait

Use this trait if you want to extend from a skeleton object and maybe
store information specific to the child in a separate table.

You'll need to adhere to a few simple rules for this to work:

  * The child class can only have the `Child` trait and must extend
    from a proper skeleton object
  * The child class can have its own database table which contains a
    reference to the parent. By default, this is the parent classname
    in lowercase appended with `_id`. You can override this via the
    class configuration `parent_field_id`.
  * If the child should not use a database table, set the
    `database_table` in the `class_configuration` of the child to
    `null`.
  * The parent class needs to have a field to store the classname of
    the child object. This value should be set via the class
    configuration `child_classname_field`.

## Cache trait

Use this trait if you want to reduce the amount of database lookups. Activating
the cache can be done per Skeleton Object:

    use \Skeleton\Object\Cache;

There are 3 caching flavours:

  * Memory: This is a per-process caching technique. Objects are stored in
    a process cache. If the same object is requested multiple times, it is
    only queried once from database.
    Be aware that this cache does not take care of other PHP processes. If
    an object gets updated in another process, your cache won't be invalidated.

    \Skeleton\Object\Config::$cache_handler = 'Memory';

  * Memcache: Caching technique with php-memcache
    (http://php.net/manual/en/book.memcache.php). Hostname, port and expire
    should be set via cache config.

    \Skeleton\Object\Config::$cache_handler = 'Memcache';
    \Skeleton\Object\Config::$cache_handler_config = [
    	'hostname' => '127.0.0.1',
    	'port' => '11211',
    	'expire' => 600,
    ];

  * Memcached: Caching technique with php-memcached
    (http://php.net/manual/en/book.memcached.php). Hostname, port and expire
    should be set via cache config.

    \Skeleton\Object\Config::$cache_handler = 'Memcached';
    \Skeleton\Object\Config::$cache_handler_config = [
    	'hostname' => '127.0.0.1',
    	'port' => '11211',
    	'expire' => 600,
    ];
