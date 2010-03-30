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
  public function migrate($dry = false, $debug = false)
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
}
