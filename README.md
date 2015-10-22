# skeleton-object

## Introduction

Functional traits to use in objects

The traits specified here take into account some special configuration parameters that can be set within the class.

### disallow_set (array)

    private static $class_configuraton = array (
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


## Slug

When using the Slug trait, it is possible to auto update the slug when saving the object (default is false)

    /**
     * Set the auto update slug flag
     */
    \Skeleton\Object\Config::$auto_update_slug = true;
