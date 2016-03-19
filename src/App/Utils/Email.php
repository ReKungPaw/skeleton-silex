<?php 

namespace App\Utils;
use Silex\Application;

class Email
{
	private $app;
	private $headers;

    public function __construct(Application $app)
    {
        $this->app = $app;

    	 // Init mail header
        $this->headers  = "Content-Type:text/html\n";
        $this->headers .= "From:noreply@example.com\n";
        $this->headers .= 'Content-type: text/html; charset=utf-8' . "\n";
    }

	public function sendRegistrationEmail($to, $hash)
    {
        $subject = 'Confirm registration';
        $body    =  $this->app['twig']->render('email-registration.html',
            array('hash' => $hash));

        mail($to, $subject,$body, $this->headers);
        return true;
    }

    public function sendForgotPasswordEmail($to, $hash)
    {
        $subject = 'Reset password';
        $body    =  $this->app['twig']->render('email-reset-password.html',
            array('hash' => $hash));
        
        mail($to, $subject,$body, $this->headers);
        return true;
    }
}