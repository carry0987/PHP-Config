<?php
require dirname(__DIR__).'/vendor/autoload.php';

use carry0987\Config\Config;
use carry0987\Sanite\Sanite;
use carry0987\Redis\RedisTool;

$redisConfig = [
    'host' => 'redis',
    'port' => 6379,
    'password' => 'test1234',
    'database' => 3
];
$redis = new RedisTool($redisConfig);
$redis->flushDatabase();

// Create a database connection
$db_config = [
    'host' => 'mariadb',
    'name' => 'dev_config',
    'user' => 'test_user',
    'password' => 'test1234',
    'charset' => 'utf8mb4',
    'port' => 3306
];
$sanite = new Sanite($db_config);

// Start config
$config = new Config($sanite->getConnection());
$config->setRedis($redis);
$config->setTableName('config')
    ->setIndexList([
        'demo_config' => 1,
        'demo_config_2' => 69
    ])->addConfig('demo_config_2', 'test');

var_dump($config->getConfig('demo_config_2'));
