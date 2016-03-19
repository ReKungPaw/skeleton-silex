<?php

// Init monolog
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../../var/logs/prod.log',
));

// Register doctrine service provider
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'dbs.options' => array (
        'mysql' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => 'skeleton',
            'user'      => 'root',
            'password'  => '123',
            'charset'   => 'utf8mb4',
        ),
    ),
));