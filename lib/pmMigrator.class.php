<?php

/**
 * Abstract class that provides an interface for performing migrations.
 */
abstract class pmMigrator
{
  /**
   * @var string
   */
  protected $class_name;

  /**
   * @var string
   */
  protected $class_fields;

  /**
   * @var array
   */
  protected $fields_hooks;

  /**
   * @var array
   */
  protected $object_hooks;

  /**
   * Constructor
   */
  public function __construct()
  {
    $this->class_name = "";
    $this->class_fields = array();
    $this->fields_hooks = array();
    $this->object_hooks = array();
  }

  /**
   * Initializes the pmMigrator with class_name and class_fields.
   * @param string $class_name The class name
   * @param array $class_fields The class fields
   *
   * @return pmMigrator
   */
  protected function init($class_name, $class_fields)
  {
    $this->setClassName(sfInflector::camelize($class_name));
    $this->setClassFields($class_fields);

    return $this;
  }

  /**
   * Create an instance of pmMigrator.
   * @param string $class_name The class name
   * @param array $class_fields The class fields
   *
   * @return pmMigrator
   */
  public static function create($class_name, $class_fields)
  {
    $class = __CLASS__;
    $migrator = new $class();
    return $migrator->init($class_name, $class_fields);
  }

  /**
   * Setter for $class_name attribute.
   * @param string $class_name The class name to migrate
   *
   * @return pmMigrator
   */
  public function setClassName($class_name)
  {
    $this->class_name = $class_name;

    return $this;
  }

  /**
   * Getter for $file attribute.
   *
   * @return string
   */
  public function getClassName()
  {
    return $this->class_name;
  }

  /**
   * Setter for $class_fields attribute.
   * @param array $class_fields The class fields to migrate
   *
   * @return pmMigrator
   */
  public function setClassFields($class_fields)
  {
    $this->class_fields = $class_fields;

    return $this;
  }

  /**
   * Getter for $class_fields attribute.
   *
   * @return string
   */
  public function getClassFields()
  {
    return $this->class_fields;
  }

  /**
   * Get a class field.
   * @param int @class_field The position
   *
   * @return string
   */
  public function getClassField($class_field)
  {
    if (array_key_exists($class_field, $this->getClassFields()))
    {
      return $this->class_fields[$class_field];
    }
    else
    {
      return null;
    }
  }

  /**
   * Add a $hook for $class_field.
   * @param string $class_field The class field
   * @param mixed $hook A valid callback for permforming the hook (could be an
   *                    array or an array of arrays)
   *
   * @return pmMigrator
   */
  public function setFieldHook($class_field, $hook)
  {
    $this->fields_hooks[$class_field] = $hook;

    return $this;
  }

  /**
   * Get a field hook field.
   * @param int @class_field The hook index
   *
   * @return mixed
   */
  public function getFieldHook($class_field)
  {
    if ($this->hasFieldHook($class_field))
    {
      return $this->fields_hooks[$class_field];
    }
    else
    {
      return null;
    }
  }

  /**
   * Returns true if the class field has hooks.
   * @param int @class_field The hook index
   *
   * @return boolean Wheather the class field has hooks or not.
   */
  public function hasFieldHook($class_field)
  {
    return array_key_exists($class_field, $this->fields_hooks);
  }

  /**
   * Setter for $fields_hooks attribute.
   * @param array An array of hooks
   *
   * @return pmMigrator
   */
  public function setFieldsHooks($hooks)
  {
    $this->fields_hooks = $hooks;

    return $this;
  }

  /**
   * Getter for $fields_hooks attribute.
   *
   * @return mixed
   */
  public function getFieldsHooks()
  {
    return $this->fields_hooks;
  }

  /**
   * Setter for $object_hooks attribute.
   * @param array An array of object hooks
   *
   * @return pmMigrator
   */
  public function setObjectHooks($hooks)
  {
    $this->object_hooks = $hooks;

    return $this;
  }

  /**
   * Getter for $object_hooks attribute.
   *
   * @return mixed
   */
  public function getObjectHooks()
  {
    return $this->object_hooks;
  }

  /**
   * Perform the migration.
   * @param boolean $dry Run in dry mode
   * @param boolean $debug Run in debug mode
   */
  public abstract function migrate($dry = false, $debug = false);

  /**
   * Create an object
   * @param bool $debug Run in debug mode
   *
   * @return mixed
   */
  protected function createObject($debug)
  {
    if ($debug)
    {
      echo "\$object = new ".$this->class_name."();\n";
    }
    $object = new $this->class_name;

    return $object;
  }

  /**
   * Populate $object $field with $value
   * @param mixed $object The object
   * @param string $field The field
   * @param string $value The value
   * @param string $debug Run in debug mode
   *
   * @return pmMigrator
   */
  protected function populateObjectField($object, $field, $value, $debug)
  {
    if ($hooks = $this->getFieldHook($field))
    {
      if (is_array($hooks[0]))
      {
        foreach ($hooks as $hook)
        {
          if (is_callable($hook))
          {
            if ($debug)
            {
              echo "Running hook for {$field}\n";
            }
            $value = call_user_func($hook, $value);
          }
          else
          {
            if ($debug)
            {
              echo "Field hook for {$field} is not callable\n";
            }
          }
        }
      }
      else
      {
        if (is_callable($hooks))
        {
          if ($debug)
          {
            echo "Running hook for {$field}\n";
          }
          $value = call_user_func($hooks, $value);
        }
        else
        {
          if ($debug)
          {
            echo "Field hook for {$field} is not callable\n";
          }
        }
      }
    }

    $setter = "set".sfInflector::camelize($field);
    if ($debug)
    {
      echo "\$object->$setter($value);\n";
    }
    call_user_func(array($object, $setter), $value);

    return $this;
  }

  /**
   * Run the object hooks
   * @param mixed $object The object
   * @param bool $debug Run in debug mode
   *
   * @return pmMigrator
   */
  protected function runObjectHooks($object, $debug)
  {
    if ($object_hooks = $this->getObjectHooks())
    {
      if (is_array($object_hooks[0]))
      {
        foreach ($object_hooks as $hook)
        {
          if (is_callable($hook))
          {
            if ($debug)
            {
              echo "Running object hook\n";
            }
            call_user_func($hook, $object);
          }
          else
          {
            if ($debug)
            {
              echo "Object hook for is not callable\n";
            }
          }
        }
      }
      else
      {
        if (is_callable($object_hooks))
        {
          if ($debug)
          {
            echo "Running object hook\n";
          }
          call_user_func($object_hooks, $object);
        }
        else
        {
          if ($debug)
          {
            echo "Object hook for is not callable\n";
          }
        }
      }
    }

    return $this;
  }

  /**
   * Run the object hooks
   * @param mixed $object The object
   * @param bool $dry Run in dry mode
   * @param bool $debug Run in debug mode
   *
   * @return pmMigrator
   */
  protected function saveObject($object, $dry, $debug)
  {
    if (!$dry)
    {
      if ($debug)
      {
        echo "Saving object\n";
      }
      $object->save();
    }

    return $this;
  }
}
