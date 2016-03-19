<?php

namespace App\Utils;


use Silex\Application;
use App\Tables;

class User
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Check if user exists
     *
     * @param $username
     * @param $email
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function existsUsername($username, $email)
    {
        $stmt = $this->app['db']->executeQuery('SELECT * FROM '. Tables::$user .
            ' WHERE username = ? or email = ?', array(strtolower($username), strtolower($email)));

        if ($stmt->fetch()) {
            return true;
        }

        return false;
    }
}