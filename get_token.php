<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/google-api-client/vendor/autoload.php';

session_start();

$credPath = __DIR__ . '/client_secret_153249119205-k4tuq3fuilai84l0vn727h40ru8a8q7p.apps.googleusercontent.com.json';
$tokenPath = __DIR__ . '/token.json';

$client = new Google_Client();
$client->setAuthConfig($credPath);
$client->addScope(Google_Service_Drive::DRIVE_FILE);
$client->setAccessType('offline');
$client->setPrompt('consent');

// Step 1: If we don't have a code, redirect user to Google's consent page
if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit;
} else {
    // Step 2: Google redirected back with code; exchange it for a token
    $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (array_key_exists('error', $accessToken)) {
        echo "❌ Error: " . htmlspecialchars($accessToken['error_description']);
        exit;
    }

    // Save token
    if (!file_put_contents($tokenPath, json_encode($accessToken))) {
        echo "❌ Failed to save token to $tokenPath";
        exit;
    }

    echo "✅ Success! Token saved to <code>$tokenPath</code><br><br>";
    echo "You can now close this window and run your backup upload script.";
}
?>
