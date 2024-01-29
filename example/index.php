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
$db_host = 'mariadb';
$db_name = 'dev_config';
$db_user = 'test_user';
$db_password = 'test1234';
$db_charset = 'utf8mb4';
$db_port = 3306;
$sanite = new Sanite($db_host, $db_name, $db_user, $db_password, $db_charset, $db_port);

// Start config
$config = new Config($sanite->getConnection());
$config->setRedis($redis);
$config->setTableName('config')
    ->setIndexList([
        'demo_config' => 1,
        'demo_config_2' => 69
    ])->addConfig('demo_config_2', 'test');

var_dump($config->getConfig('demo_config_2'));
