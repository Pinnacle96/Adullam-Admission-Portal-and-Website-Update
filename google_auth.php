<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/google-api-client/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secret_153249119205-45715c9j39g6419k2pl3pn0h6b1gnufc.apps.googleusercontent.com.json');
$client->addScope(Google_Service_Drive::DRIVE_FILE);
$client->setAccessType('offline');
$client->setRedirectUri((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

$tokenPath = __DIR__ . '/token.json';

if (!file_exists($tokenPath)) {
    if (!isset($_GET['code'])) {
        $authUrl = $client->createAuthUrl();
        echo "<a href='$authUrl'>Click here to connect your Google Drive</a>";
        exit;
    } else {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        file_put_contents($tokenPath, json_encode($token));
        echo "✅ Token saved. You can now run backups.";
        exit;
    }
} else {
    echo "✅ Token already exists.";
}
