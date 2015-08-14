# skeleton-object
Functional traits to use in objects


Initial version of the usage of traits.

The traits specified here take into account some special configuration parameters that can be set within the class.

* disallow_set (array)

    private static $class_configuraton = array (
      'disallow_set' => array (
        'directory',
        'full_path',
        'password',
      ),
    );

  Prevents the direct setting of some class variables

* database_table (string)

    private static $class_configuration = array (
      'database_table' = 'my_super_special_class',
    );

  Overrides the default strtolower(get_class()) as tablename

* database_config_name (string)

    private static $class_configuration = array (
      'database_config_name' = 'database_dns',
    );

  Overrides the default database when using Database::get();
