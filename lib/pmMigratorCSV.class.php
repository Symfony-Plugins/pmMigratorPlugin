<?php

/**
 * Concrete class that provides an implementation for performing migrations.
 * This class migrates CSV files into objects.
 */
class pmMigratorCSV extends pmMigrator
{
  /**
   * @var string The CSV file path
   */
  protected $file;

  /**
   * Create an instance of pmMigratorCSV.
   * @param string $file The path to the CSV file
   * @param string $class_name The class name
   * @param array $class_fields The class fields
   *
   * @return pmMigratorCSV
   */
  public static function create($file, $class_name, $class_fields)
  {
    $class = __CLASS__;
    $migrator = new $class();
    $migrator->init($class_name, $class_fields);
    $migrator->setFile($file);

    return $migrator;
  }

  /**
   * Setter for $file attribute.
   * @param string The CSV file path
   *
   * @return pmMigratorCSV
   */
  public function setFile($file)
  {
    $this->file = $file;
    return $this;
  }

  /**
   * Getter for $file attribute.
   *
   * @return string
   */
  public function getFile()
  {
    return $this->file;
  }

  /**
   * Perform the migration.
   * @param boolean $dry Run in dry mode
   * @param boolean $debug Run in debug mode
   */
  public function toDB($dry = false, $debug = false)
  {
    $handle = fopen($this->getFile(), "r");

    if ($handle)
    {
      while($data = fgetcsv($handle))
      {
        $object = $this->createObject($debug);

        for ($i = 0; $i < count($data); $i++)
        {
          if (!is_null($this->getClassField($i)))
          {
            $value = $data[$i];
            $this->populateObjectField($object, $this->getClassField($i), $value, $debug);
          }
        }

        $this->runObjectHooks($object, $debug);
        $this->saveObject($object, $dry, $debug);
      }

      fclose($handle);
    }
    else
    {
      throw new Exception("File {$this->getFile()} could not be opened.");
    }
  }

  /**
   * Perform the migration into a fixture file.
   * @param boolean $dry Run in dry mode
   * @param boolean $debug Run in debug mode
   */
  public function toFixture($fixture_name = null, $dry = false, $debug = false)
  {
    $handle = fopen($this->getFile(), "r");

    $objects = array();
    $nb_lines = 0;

    if ($handle)
    {
      while($data = fgetcsv($handle))
      {
        $object = $this->createObject($debug);

        for ($i = 0; $i < count($data); $i++)
        {
          if (!is_null($this->getClassField($i)))
          {
            $value = $data[$i];
            $this->populateObjectField($object, $this->getClassField($i), $value, $debug);
          }
        }

        $this->runObjectHooks($object, $debug);

        $objects[] = $object;
      }

      fclose($handle);

      if ($dry)
      {
        fwrite(STDOUT, sfYaml::dump($this->toArray($objects), 3));
      }
      else
      {
        if (is_null($fixture_name))
        {
          $fixture_name = sfInflector::underscore($this->getClassName());
        }
        $fixture_handle = fopen(sfConfig::get('sf_data_dir')."/fixtures/$fixture_name.yml", "w");
        fwrite($fixture_handle, sfYaml::dump($this->toArray($objects), 3));
      }
    }
    else
    {
      throw new Exception("File {$this->getFile()} could not be opened.");
    }
  }

  private function toArray($array_of_objects)
  {
    if (!count($array_of_objects))
    {
      return array();
    }

    $array = array($this->getClassName() => array());

    $count = 0;
    foreach ($array_of_objects as $object)
    {
      $index = $this->getClassName().'_'.++$count;
      $array[$this->getClassName()][$index] = $this->fixKeyNames($object->toArray());
    }

    return $array;
  }

  private function fixKeyNames($array)
  {
    $ret = array();
    foreach ($array as $k => $v)
    {
      $k = sfInflector::underscore($k);
      $ret[$k] = $v;
    }
    return $ret;
  }
}
