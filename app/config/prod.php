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
            'host'      => null,
            'dbname'    => null,
            'user'      => null,
            'password'  => null,
            'charset'   => 'utf8mb4',
        ),
    ),
));