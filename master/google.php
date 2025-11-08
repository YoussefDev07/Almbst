<?php
require("./assets/libs/php/google-api-php-client/vendor/autoload.php");

$google = new Google\Client();
$google->setClientId("((CLIENT_ID)).apps.googleusercontent.com");
$google->setClientSecret("((CLIENT_SECERT))");
$google->addScope("email");
$google->addScope("profile");
$google->setRedirectUri("http://localhost/www/%d8%a7%d9%84%d9%85%d8%a8%d8%b3%d8%b7/account.php");

# https://code-boxx.com/php-login-with-google/