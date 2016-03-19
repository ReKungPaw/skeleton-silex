<?php

use Silex\Application;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../templates',
));

$app->register(new Silex\Provider\ValidatorServiceProvider());

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'secure_area_admin' => array(
            'pattern' => '^/admin/',
            'form' => array('login_path' => '/', 'check_path' => '/admin/login_check'),
            'logout' => array('logout_path' => '/admin/logout', 'invalidate_session' => true),
            'users' => function () use ($app) {
                return new App\Service\UserProvider($app['db']);
            },
        ),
    )
));

$app['security.encoder.digest'] = function () {
    return new MessageDigestPasswordEncoder('sha1', false, 1);
};

$app->register(new App\Service\RegistrationProvider(), array());

return $app;



