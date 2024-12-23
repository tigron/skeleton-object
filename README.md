# skeleton-object

## Introduction

This library exists of multiple traits that can be added to your class.
Each trait will add more functionality to the class.
To use a trait, add the following line inside you class definition:

    use \Skeleton\Object\TRAIT;

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

    $object->get_classname()

returns the classname of the object

    Object::get_by_id($id)

returns the object with id '$id'

    Object::get_by_ids($ids)

returns all objects with given ids

    Object::get_all($sort, $direction)

returns all objects. If the an archived column exists, this will be taken into account

Optional parameters:

    $sort: the field to sort on
    $direction: ASC/DESC

## Slug trait

The Slug trait creates an easy-to-read string that can be used in a URL to
identify a specific page.
Slug creation is done in 3 steps:

### Check if the slug needs to be generated or regenerated

- If save() is requested on a new object, slug creation will proceed
- trait_slug_needs_regeneration(): bool is called to check if an existing slug
needs to be regenerated. By default trait_sltrait_slug_needs_regeneration()
returns false and slug isnever regenerated.

### Find a base string

In order to find the base string, the method trait_slug_get_base(): string is
called. By default it searches for the value in property 'name' or property
'text_en_name' where 'en' is replaced by the base language.
The field can be specified in $class_configuration['sluggable'].

### Generate the slug from base string

The base string is converted into lowercase ASCII characters. Spaces are
replaced by '-'.

### Make the slug unique

To make the slug unique, the method trait_slug_unique($slug): $unique is called.
If a slug already exists, this methods appends hexadecimal values to the slug
until the slug becomes unique.

## UUID trait

When the UUID trait is in use, the field with the name "uuid" will be
populated with a random and unique UUIDv4.

## Number trait

The number trait will generate a unique number for each object. In contrast
with the primary key (id), the number field will only be unique within a given
set of 'number_dividers', an array of field names.

For example: create a unique number per item in an invoice

    Invoice X
       Invoice_Item 1
       Invoice_Item 2
       Invoice_Item 3
    Invoice Y
       Invoice_Item 1
       Invoice_Item 2
       Invoice_Item 3

For this example, the class_configuration needs to be defined as:

    private static $class_configuration = array (
      'number_dividers' => [ 'invoice_id' ], // A unique number is created per object with the same invoice_id
      'number_field' => 'number', // Store the number in field 'number'
    );


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
  * A child can be casted into another child class via

    $new_class = $object->cast('another_child_class');

## Cache trait

Use this trait if you want to reduce the amount of database lookups. Activating
the cache can be done per Skeleton Object:

    use \Skeleton\Object\Cache;

There are 3 caching flavours:

### Memory
This is a per-process caching technique. Objects are stored in
a process cache. If the same object is requested multiple times, it is
only queried once from database.
Be aware that this cache does not take care of other PHP processes. If
an object gets updated in another process, your cache won't be invalidated.

    \Skeleton\Object\Config::$cache_handler = 'Memory';

### Memcache
Caching technique with php-memcache
(http://php.net/manual/en/book.memcache.php). Hostname, port and expire should
be set via cache config.

    \Skeleton\Object\Config::$cache_handler = 'Memcache';
    \Skeleton\Object\Config::$cache_handler_config = [
    	'hostname' => '127.0.0.1',
    	'port' => '11211',
    	'expire' => 600,
    ];

### Memcached
Caching technique with php-memcached
(http://php.net/manual/en/book.memcached.php). Hostname, port and expire
should be set via cache config.

    \Skeleton\Object\Config::$cache_handler = 'Memcached';
    \Skeleton\Object\Config::$cache_handler_config = [
    	'hostname' => '127.0.0.1',
    	'port' => '11211',
    	'expire' => 600,
    ];

## Class configuration

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

Overrides the default tablename
default: strtolower(get_class())

###  database_config_name (string)

    private static $class_configuration = [
      'database_config_name' => 'database_dsn',
	];

Overrides the default database connection
default: Database::get();

###  table_field_id (string)

	private static $class_configuration = [
	  'table_field_id' => 'my_strange_id',
	];

Overrides the default primary key field
default: 'id'

###  table_field_created (string)

	private static $class_configuration = [
	  'table_field_created' => 'my_strange_created_field',
	];

Overrides the default field to store the created timestamp
default: 'created'

###  table_field_updated (string)

	private static $class_configuration = [
	  'table_field_updated' => 'my_strange_updated_field',
	];

Overrides the default field to store the last updated timestamp
default: 'updated'

###  table_field_archived (string)

	private static $class_configuration = [
	  'table_field_archived' => 'my_strange_updated_archived',
	];

Overrides the default field to store the archived timestamp
default: 'archived'

###  sluggable (string)

	private static $class_configuration = [
	  'sluggable' => 'my_field_to_slug',
	];

Overrides the field which is used to create the slug
default: 'name'

### number_dividers (array)

	private static $class_configuration = [
	  'number_dividers' => [ 'group1', 'group2' ],
	];

Defines the fields that group objects with a unique number together
default: []

### number_field (string)

	private static $class_configuration = [
	  'number_field' => [ 'number' ],
	];

Defines the field in which the unique number should be stored
default: null

