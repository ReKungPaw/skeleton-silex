<?php

namespace App\Service;

use Silex\ServiceProviderInterface;
use Silex\Application;
use App\Tables;

class RegistrationProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['registration'] = $app->protect(function ($username, $role, $password, $email, $hash) use ($app) {
            $app['db']->insert(Tables::$user, array(
                'username'  => $username,
                'roles' => $role,
                'password'  => $password,
                'optin' => 0,
                'email' => $email,
                'registration_hash' => $hash,
                'active' => 0
            ));

            return true;
        });
    }

    public function boot(Application $app)
    {
    }
}