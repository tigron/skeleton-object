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

###  sluggable (string)

	private static $class_configuration = [
	  'sluggable' => 'my_field_to_slug',
	];


## Available functions

### Trait Model
  This trait provides basic functionality for an object in the database
    $object = new MyClass();
    $object->database_field_1 = $value1;
    $object->database_field_2 = $value2;

    $dirty_fields = $object->get_dirty_fields();
  This variable now contains [ 'database_field_1', 'database_field_2' ]

    $object->load_array( $_POST['form_for_object'] );

### Trait Delete

    $object->delete()

  deletes the object permanently

    $object->archive()

  stores the archive-date in field 'archived'

    $object->restore()

  resets the field 'archived'

## Trait Get

    $object->get_info()

  returns an array containing all database fields of the object

    Object::get_by_id($id)

  returns the object with id '$id'

    Object::get_all($sort, $direction)

  returns all object.
  Optional parameters:
    $sort: the field to sort on
    $direction: ASC/DESC

## Trait Slug

When using the Slug trait, it is possible to auto update the slug when saving the object (default is false)

    /**
     * Set the auto update slug flag
     */
    \Skeleton\Object\Config::$auto_update_slug = true;

## Trait Child

Use this trait if you want to extend from a Skeleton Object to store child-specific information. There are some rules to follow here:
- The Child class only needs to have trait 'Child' and must extend from a Skeleton Object
- The Child class must have its own database table which contains a reference to the parent. By default $PARENT_CLASS . '_id' is used. This can be overwritten via the class configuration 'parent_field_id'
- The parent class needs to have a field to store the classname of the child object. This value should be set via the class configuration 'child_classname_field'
