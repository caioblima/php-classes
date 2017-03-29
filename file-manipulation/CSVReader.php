<?php
/**
 * Class CSVReader
 * Provides abstration to receive an CSV, format him to associative array
 * Uses Factory Static Method to Create many instances you want. 
 * This is because you want to generate some data, work on him outside the class or you dont want to use the import multiple functionality.
 * @author Caio Lima
 */
final class CSVReader
{
  private $import_data = [];
  private $import_dir;
  private $file_handle;
  private $file;
  private $files;
  private $type;
  public $tulep_import_arr = [];
  static $csv_data = null;
  static $index = 0;
  static $row = null;
  /**
   * Static Factory Method prepare
   *
   * Método fábrica de objetos da classe
   *
   * @param string $files_dir Could it be a regex filepath or a single filepath
   * @param string $type Type of import - Single or Multiple
   */
  final static function prepare($files_dir = null, $type = 'single')
  {
    if(empty($files_dir))
      throw new Exception("files_dir no passed in parameter");

    if (is_array($files_dir) && count($files_dir))
      $type = 'multiple';

    return new static($files_dir, $type);
  }
  /**
   * Class constructor
   *
   * Class constructor to set basic properties of the class
   *
   * @param string $files_dir Could it be a regex filepath or a single filepath
   * @param string $type Type of import - Single or Multiple
   */
  private function __construct($files_dir, $type) {
    if ($type == 'single')
      $this->file = $files_dir;
    else
      $this->files = $this->prepareMultipleFiles($files_dir);

    $this->type = $type;
  }
  /**
   * Method prepareMultipleFiles
   *
   * Class constructor to set basic properties of the class
   *
   * @param string $files_dir Could it be a regex filepath or a single filepath
   */
  final public function prepareMultipleFiles($files_dir)
  {
    //Exprect a regex to get the files dir
    if (count($files_dir) == 1)
      $files_dir = glob($files_dir[0]);
    return $files_dir;
  }
  /**
   * Method openFileHandle
   *
   * Encapsules PHP fopen function
   *
   * @param string $file See php.net fopen
   * @param string $option See php.net fopen
   */
  final private function openFileHandle($file, $option = 'r')
  {
    $this->file_handle = fopen($file, $option);
    return $this->file_handle;
  }
  /**
   * Method closeFileHandle
   *
   * Encapsules PHP close function
   *
   * @param void
   */
  final private function closeFileHandle()
  {
    return (is_resource($this->file_handle)) ? fclose($this->file_handle) : false;
  }
  /**
   * Method closeFileHandle
   *
   * Method to process the csv according ta what you set on Factory startup
   *
   * @param void
   */
  final public function generateImportData()
  {
    if ($this->type == 'single') return $this->processDataFromSingleFile();
    return $this->processDataFromMultipleFiles();
  }
  /**
   * Method processDataFromSingleFile
   *
   * Process a single csv file
   *
   * @param void
   */
  final public function processDataFromSingleFile()
  {
    $this->openFileHandle($this->file);
    self::$row = 0;
    if ($this->file_handle) {
      while (self::$csv_data = fgetcsv($this->file_handle, 0)) {
        if (self::$row == 0) {
          $cols = self::$csv_data;
          $this->import_data['cols'] = self::$csv_data;
        } else {
          foreach ($cols as $index => $value) {
            $this->import_data['lines'][self::$row][$value] = self::$csv_data[$index];
          }
        }
        self::$row++;
      }
    }
    $this->closeFileHandle();
    return $this;
  }
  /**
   * Method processDataFromMultipleFiles
   *
   * Process multiple csv files
   *
   * @param void
   */
  final public function processDataFromMultipleFiles()
  {
    foreach ($this->files as $index => $file) {
      $file_name_regex = preg_match("~[^/]*(?=\.[^.]+($|\?))~", $file, $matches);
      $file_name = (isset($matches[0])) ? $matches[0] : $file;
      $this->openFileHandle($file);
      if ($this->file_handle) {
        //Flush row and data to clean up memory pointers
        self::$row = 0;
        self::$csv_data = null;
        while (self::$csv_data = fgetcsv($this->file_handle, 0)) {
          if (self::$row == 0) {
            $cols = self::$csv_data;
            $this->import_data[$file_name]['cols'] = self::$csv_data;
          } else {
            foreach ($cols as $index => $value) {
              $this->import_data[$file_name]['lines'][self::$row][$value] = self::$csv_data[$index];
            }
          }
          self::$row++;
        }
        $this->closeFileHandle();
      }
    }
    return $this;
  }
  /**
   * Method getFullDataToImport
   *
   * Return the csv formatted in arrays
   *
   * @param void
   */
  final public function getFullDataToImport()
  {
    return $this->import_data;
  }
}

$csv_reader = CSVReader::prepare('tuleap-import/import/espm.csv');
$generated_data = $csv_reader->generateImportData()->getFullDataToImport();
print_r($generated_data);