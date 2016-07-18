<?php
namespace Experiment;
use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Plugin\PluginInterface;

use yii\base\Object;
use yii\db\Connection;
use yii\di\Container;

interface UserFinderInterface
{
    function findUser();
}

class Plugin extends Object implements UserFinderInterface
{
    public $db;

    public function __construct(Connection $db, $config = [])
    {
        $this->db = $db;
        parent::__construct($config);
    }

    public function findUser()
    {
    }
}

$container = new Container;
$container->set('yii\db\Connection', [
    'dsn' => '...',
]);
$container->set('app\models\UserFinderInterface', [
    'class' => 'app\models\UserFinder',
]);

// which is equivalent to:

$db = new \yii\db\Connection(['dsn' => '...']);
$finder = new Plugin($db);
