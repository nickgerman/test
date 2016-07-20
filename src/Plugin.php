<?php
namespace Experiment;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Plugin\PluginInterface;

use Yii;
use yii\base\Component;
use yii\base\Object;
use yii\db\Connection;

class Plugin extends Object implements PluginInterface
{

  public function __construct(Connection $db, $config = [])
  {
      $this->db = $db;
      parent::__construct($config);
  }

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        print_r("\n\rActivated.\n\r");
    }

    private function createTable()
    {
      print_r("create table here...\n\r");
    }
}
