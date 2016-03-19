<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Silex\Application\SecurityTrait;

use App\Tables;
use App\Service\Register;
use App\Utils\User;
use App\Utils\Email;

// Get current username
$app['username'] = function ($app) {
    // Get token
    $token     = $app['security.token_storage']->getToken();
    $user      = $token->getUser();
    return $user->getUsername();
};

// Login controller
$app->get('/', function(Request $request) use ($app) {
    return $app['twig']->render('login.html', array(
        'error'         => $app['security.last_error']($request),
        'last_username' => $app['session']->get('_security.last_username'),
    ));
})->bind('login');

// Forgot password controller
$app->match('/forgot-password', function(Request $request) use ($app) {
    // On get request return html template
    if ($request->getMethod() == 'GET') {
        return $app['twig']->render('forgot-password.html', array());
    }

    // Check if user input a valid email address
    $email = $request->get('email', null);
    $validate = $app['validator']->validate($email, new Assert\Email());

    // If something went wrong with email addres, redirect back to 
    // forgot password page
    if (count($validate)) {
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'danger', 'text' => 'Invalid email address'));
        return $app->redirect($app['url_generator']->generate('forgot-password'));
    }

    // Check if user found for given email address
    $user = $app['db']->executeQuery('Select id FROM ' . Tables::$user . 
                                     ' WHERE email=? and active=1 and optin=1', 
                                     array($email))->fetch();

    // If no user found for given email address, return back to forgot-password page
    if (!$user) {
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'danger', 'text' => 'No user found'));
        return $app->redirect($app['url_generator']->generate('forgot-password'));
    }

    // Generate hash for forgot password
    $hash = md5(time());

    // Update table with hash for this user
    $app['db']->executeUpdate('UPDATE ' . Tables::$user . 
                              ' set forgot_password_hash=?,forgot_password_date=? WHERE id=?',
                              array($hash, date('Y-m-d H:i:s'), $user['id']));

    // Add success message with information that the link is only valid for 24h
    $app['session']->getFlashBag()->add('message', 
            array('type' => 'success', 'text' => 'You get an email for reseting your password, which will be valid for 24h'));
    
    // Send user email    
    $emailObject = new Email($app);
    $emailObject->sendForgotPasswordEmail($email, $hash);

    // Redirect to login page
    return $app->redirect($app['url_generator']->generate('login'));
})->bind('forgot-password')->method('GET|POST');;

// Registration controller
$app->match('/register', function(Request $request) use ($app) {
    // On GET method return html template
    if ($request->getMethod() == 'GET') {
        return $app['twig']->render('register.html', array());
    }

    // Try to get posted data
    $data = [
      'username' => $request->get('username', null),
      'password' => $request->get('password', null),
      'repeat_password' => $request->get('repeat_password', null),
      'email' => $request->get('email')
    ];

    // Validate form values
    $errors = array();
    $errors['email']    = $app['validator']->validate($data['email'], new Assert\Email());
    $errors['username'] = $app['validator']->validate($data['username'], new Assert\NotBlank());
    $errors['password'] = $app['validator']->validate($data['password'], new Assert\NotBlank());
    $errors['repeat_password'] = $app['validator']->validate($data['repeat_password'], new Assert\NotBlank());

    // Check if user exists
    // Username and email must be unique
    $user = new User($app);
    $errors['exists_user'] = $user->existsUsername($data['username'], $data['email']);

    // Check if passwords are equal
    $errors['not_equal_passwords'] = false;
    if ($data['password'] != $data['repeat_password']) {
        $errors['not_equal_passwords'] = true;
    }

    // Check validation
    if (count($errors['email']) || count($errors['username']) ||
        count($errors['password']) || count($errors['repeat_password']) ||
        $errors['exists_user'] || $errors['not_equal_passwords']) {
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'danger', 'text' => 'Invalid input'));
    } else {
        // Generate registration hash for optin user
        $hash = md5(time());

        // Encrypt password
        $encryptedPassword = $app['security.encoder.digest']->encodePassword($data['password'], '');
        
        // Register user
        $app['registration']($data['username'],'ROLE_USER', $encryptedPassword, $data['email'], $hash);

        // Add success message
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'success', 'text' => 'You have successful registered, now confirm your email address'));
        
        // Send email to user
        $email = new Email($app);
        $email->sendRegistrationEmail($data['email'], $hash);

        // Redirect to login page
        return $app->redirect($app['url_generator']->generate('login'));
    }

    // On invalid form data return html template
    return $app['twig']->render('register.html');
})->bind('register')->method('GET|POST');

// Optin controller
$app->get('/confirm/{hash}', function(Request $request) use ($app) {
    // Hash must be set
    if ( ($hash = $request->get('hash', null)) === null) {
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'danger', 'text' => 'Hash not found'));
        return $app->redirect($app['url_generator']->generate('login'));
    }

    // Find user
    $user = $app['db']->executeQuery('Select * FROM ' . Tables::$user . ' WHERE registration_hash=?', 
                                        array($hash))->fetch();

    // If no user found for this hash redirect with error message to login page
    if ($user == false) {
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'danger', 'text' => 'User not found'));
        return $app->redirect($app['url_generator']->generate('login'));
    }

    // Set optin flag to 1
    $app['db']->executeUpdate('UPDATE ' . Tables::$user . 
                              ' set optin=1, active=1,registration_hash=null WHERE id=?', 
                              array($user['id']));

    // Add success message
    $app['session']->getFlashBag()->add('message', 
            array('type' => 'success', 'text' => 'Confirm successful'));

    // Return to login page
    return $app->redirect($app['url_generator']->generate('login'));

})->bind('confirm');

// Reset password controller
$app->match('/reset/{hash}', function(Request $request) use ($app) {
    // Hash must be set
    if ( ($hash = $request->get('hash', null)) === null) {
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'danger', 'text' => 'Hash not found'));
        return $app->redirect($app['url_generator']->generate('login'));
    }

    // Find user
    $user = $app['db']->executeQuery('Select id,forgot_password_date FROM ' . Tables::$user . ' WHERE forgot_password_hash=?', array($hash))->fetch();

    // Check if user exists
    if ($user == false) {
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'danger', 'text' => 'User not found'));
        return $app->redirect($app['url_generator']->generate('login'));
    }

    // Check if linkg expired
    if (strtotime($user['forgot_password_date']) < strtotime('now -1 day')) {
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'danger', 'text' => 'Url expired'));
        return $app->redirect($app['url_generator']->generate('login'));
    }

    // Check if new password was posted
    if ($request->get('send', null) !== null) {
        // Get posted passwords
        $password = $request->get('password', null);
        $repeatPassword = $request->get('repeat_password', null);

        // Check if passwords are equal
        if (is_null($repeatPassword) || is_null($password) || $password != $repeatPassword) {
            $app['session']->getFlashBag()->add('message', 
                array('type' => 'danger', 'text' => 'Passwords not equal'));
            return $app['twig']->render('reset-password.html', array('hash' => $hash));
        }

        // Encrypt password
        $encryptedPassword = $app['security.encoder.digest']->encodePassword($password, '');
        
        // Set new password
        $app['db']->executeUpdate('UPDATE ' . Tables::$user . ' set password=?,forgot_password_hash=null WHERE id=?', array($encryptedPassword,$user['id']));

        // Add success message
        $app['session']->getFlashBag()->add('message', 
            array('type' => 'success', 'text' => 'Password reset successful'));

        // Redirect to login page
        return $app->redirect($app['url_generator']->generate('login'));
    }

    // On default show reset-password html template
    return $app['twig']->render('reset-password.html', array('hash' => $hash));
})->bind('reset');

// Demo controller after login
$app->get('/admin/homepage', function() use ($app) {
    return $app['twig']->render('homepage.html', array());
})->bind('homepage');
