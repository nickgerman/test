<?php
namespace Experiment;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Plugin\PluginInterface;
use Exception;
use PDO;
use PDOException;

class Plugin
{
  // Same as mysqldump
  const MAXLINESIZE = 1000000;

  // Available compression methods as constants
  const GZIP = 'Gzip';
  const BZIP2 = 'Bzip2';
  const NONE = 'None';

  // Available connection strings
  const UTF8 = 'utf8';
  const UTF8MB4 = 'utf8mb4';

  /**
  * Database username
  * @var string
  */
  public $user;
  /**
  * Database password
  * @var string
  */
  public $pass;
  /**
  * Connection string for PDO
  * @var string
  */
  public $dsn;
  /**
  * Destination filename, defaults to stdout
  * @var string
  */
  public $fileName = 'php://output';

  // Internal stuff
  private $tables = array();
  private $views = array();
  private $triggers = array();
  private $procedures = array();
  private $dbHandler;
  private $dbType;
  private $compressManager;
  private $typeAdapter;
  private $dumpSettings = array();
  private $pdoSettings = array();
  private $version;
  private $tableColumnTypes = array();
  /**
  * database name, parsed from dsn
  * @var string
  */
  private $dbName;
  /**
  * host name, parsed from dsn
  * @var string
  */
  private $host;
  /**
  * dsn string parsed as an array
  * @var array
  */
  private $dsnArray = array();

  /**
   * Constructor of Mysqldump. Note that in the case of an SQLite database
   * connection, the filename must be in the $db parameter.
   *
   * @param string $dsn        PDO DSN connection string
   * @param string $user       SQL account username
   * @param string $pass       SQL account password
   * @param array  $dumpSettings SQL database settings
   * @param array  $pdoSettings  PDO configured attributes
   */
  public function __construct(
      $dsn = '',
      $user = '',
      $pass = '',
      $dumpSettings = array(),
      $pdoSettings = array()
  ) {
      $dumpSettingsDefault = array(
          'include-tables' => array(),
          'exclude-tables' => array(),
          'compress' => Plugin::NONE,
          'no-data' => false,
          'add-drop-table' => false,
          'single-transaction' => true,
          'lock-tables' => true,
          'add-locks' => true,
          'extended-insert' => true,
          'complete-insert' => false,
          'disable-keys' => true,
          'where' => '',
          'no-create-info' => false,
          'skip-triggers' => false,
          'add-drop-trigger' => true,
          'routines' => false,
          'hex-blob' => true, /* faster than escaped content */
          'databases' => false,
          'add-drop-database' => false,
          'skip-tz-utc' => false,
          'no-autocommit' => true,
          'default-character-set' => Plugin::UTF8,
          'skip-comments' => false,
          'skip-dump-date' => false,
          'init_commands' => array(),
          /* deprecated */
          'disable-foreign-keys-check' => true
      );

      $pdoSettingsDefault = array(
          PDO::ATTR_PERSISTENT => true,
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      );

      $this->user = $user;
      $this->pass = $pass;
      $this->parseDsn($dsn);
      $this->pdoSettings = self::array_replace_recursive($pdoSettingsDefault, $pdoSettings);
      $this->dumpSettings = self::array_replace_recursive($dumpSettingsDefault, $dumpSettings);

      $this->dumpSettings['init_commands'][] = "SET NAMES " . $this->dumpSettings['default-character-set'];

      if (false === $this->dumpSettings['skip-tz-utc']) {
          $this->dumpSettings['init_commands'][] = "SET TIME_ZONE='+00:00'";
      }

      $diff = array_diff(array_keys($this->dumpSettings), array_keys($dumpSettingsDefault));
      if (count($diff)>0) {
          throw new Exception("Unexpected value in dumpSettings: (" . implode(",", $diff) . ")");
      }

      if ( !is_array($this->dumpSettings['include-tables']) ||
          !is_array($this->dumpSettings['exclude-tables']) ) {
          throw new Exception("Include-tables and exclude-tables should be arrays");
      }

      // Dump the same views as tables, mimic mysqldump behaviour
      $this->dumpSettings['include-views'] = $this->dumpSettings['include-tables'];

      // Create a new compressManager to manage compressed output
      $this->compressManager = CompressManagerFactory::create($this->dumpSettings['compress']);
  }

  /**
   * Custom array_replace_recursive to be used if PHP < 5.3
   * Replaces elements from passed arrays into the first array recursively
   *
   * @param array $array1 The array in which elements are replaced
   * @param array $array2 The array from which elements will be extracted
   *
   * @return array Returns an array, or NULL if an error occurs.
   */
  public static function array_replace_recursive($array1, $array2)
  {
      if (function_exists('array_replace_recursive')) {
          return array_replace_recursive($array1, $array2);
      }

      foreach ($array2 as $key => $value) {
          if (is_array($value)) {
              $array1[$key] = self::array_replace_recursive($array1[$key], $value);
          } else {
              $array1[$key] = $value;
          }
      }
      return $array1;
  }

  /**
   * Parse DSN string and extract dbname value
   * Several examples of a DSN string
   *   mysql:host=localhost;dbname=testdb
   *   mysql:host=localhost;port=3307;dbname=testdb
   *   mysql:unix_socket=/tmp/mysql.sock;dbname=testdb
   *
   * @param string $dsn dsn string to parse
   */
  private function parseDsn($dsn)
  {
      if (empty($dsn) || (false === ($pos = strpos($dsn, ":")))) {
          throw new Exception("Empty DSN string");
      }

      $this->dsn = $dsn;
      $this->dbType = strtolower(substr($dsn, 0, $pos));

      if (empty($this->dbType)) {
          throw new Exception("Missing database type from DSN string");
      }

      $dsn = substr($dsn, $pos + 1);

      foreach(explode(";", $dsn) as $kvp) {
          $kvpArr = explode("=", $kvp);
          $this->dsnArray[strtolower($kvpArr[0])] = $kvpArr[1];
      }

      if (empty($this->dsnArray['host']) &&
          empty($this->dsnArray['unix_socket'])) {
          throw new Exception("Missing host from DSN string");
      }
      $this->host = (!empty($this->dsnArray['host'])) ?
          $this->dsnArray['host'] :
          $this->dsnArray['unix_socket'];

      if (empty($this->dsnArray['dbname'])) {
          throw new Exception("Missing database name from DSN string");
      }

      $this->dbName = $this->dsnArray['dbname'];

      return true;
  }

  /**
   * Connect with PDO
   *
   * @return null
   */
  private function connect()
  {
      // Connecting with PDO
      try {
          switch ($this->dbType) {
              case 'sqlite':
                  $this->dbHandler = @new PDO("sqlite:" . $this->dbName, null, null, $this->pdoSettings);
                  break;
              case 'mysql':
              case 'pgsql':
              case 'dblib':
                  $this->dbHandler = @new PDO(
                      $this->dsn,
                      $this->user,
                      $this->pass,
                      $this->pdoSettings
                  );
                  // Execute init commands once connected
                  foreach($this->dumpSettings['init_commands'] as $stmt) {
                      $this->dbHandler->exec($stmt);
                  }
                  // Store server version
                  $this->version = $this->dbHandler->getAttribute(PDO::ATTR_SERVER_VERSION);
                  break;
              default:
                  throw new Exception("Unsupported database type (" . $this->dbType . ")");
          }
      } catch (PDOException $e) {
          throw new Exception(
              "Connection to " . $this->dbType . " failed with message: " .
              $e->getMessage()
          );
      }

      $this->dbHandler->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
      $this->typeAdapter = TypeAdapterFactory::create($this->dbType, $this->dbHandler);
  }

  /**
   * Main call
   *
   * @param string $filename  Name of file to write sql dump to
   * @return null
   */
  public function start($filename = '')
  {
      // Output file can be redefined here
      if (!empty($filename)) {
          $this->fileName = $filename;
      }

      // Connect to database
      $this->connect();

      // Create output file
      $this->compressManager->open($this->fileName);

      // Write some basic info to output file
      $this->compressManager->write($this->getDumpFileHeader());

      // Store server settings and use sanner defaults to dump
      $this->compressManager->write(
          $this->typeAdapter->backup_parameters($this->dumpSettings)
      );

      if ($this->dumpSettings['databases']) {
          $this->compressManager->write(
              $this->typeAdapter->getDatabaseHeader($this->dbName)
          );
          if ($this->dumpSettings['add-drop-database']) {
              $this->compressManager->write(
                  $this->typeAdapter->add_drop_database($this->dbName)
              );
          }
      }

      // Get table, view and trigger structures from database
      $this->getDatabaseStructure();

      if ($this->dumpSettings['databases']) {
          $this->compressManager->write(
              $this->typeAdapter->databases($this->dbName)
          );
      }

      // If there still are some tables/views in include-tables array,
      // that means that some tables or views weren't found.
      // Give proper error and exit.
      // This check will be removed once include-tables supports regexps
      if (0 < count($this->dumpSettings['include-tables'])) {
          $name = implode(",", $this->dumpSettings['include-tables']);
          throw new Exception("Table (" . $name . ") not found in database");
      }

      $this->exportTables();
      $this->exportViews();
      $this->exportTriggers();
      $this->exportProcedures();

      // Restore saved parameters
      $this->compressManager->write(
          $this->typeAdapter->restore_parameters($this->dumpSettings)
      );
      // Write some stats to output file
      $this->compressManager->write($this->getDumpFileFooter());
      // Close output file
      $this->compressManager->close();
  }
}
