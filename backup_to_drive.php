<?php
ini_set('max_execution_time', 300);  // 5 minutes
ini_set('max_input_time', 300);

require_once __DIR__ . '/google-api-client/vendor/autoload.php';

date_default_timezone_set('Africa/Lagos');

// === CONFIG
$dbHost = 'localhost';
$dbUser = 'adullamn_adullamn';
$dbPass = 'Rq;u54Y77#QFxx';
$dbName = 'adullamn_cams';

$backupDir = __DIR__ . '/backups';
$dbFile = "$backupDir/db_" . date('Y-m-d_H-i-s') . ".sql";
$zipFile = "$backupDir/site_" . date('Y-m-d_H-i-s') . ".zip";
$credPath = __DIR__ . '/client_secret_153249119205-45715c9j39g6419k2pl3pn0h6b1gnufc.apps.googleusercontent.com.json';
$tokenPath = __DIR__ . '/token.json';

// === Ensure backup folder exists
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

// === Google Client Setup
$client = new Google_Client();
$client->setAuthConfig($credPath);
$client->addScope(Google_Service_Drive::DRIVE_FILE);
$client->setAccessType('offline');
$client->setRedirectUri((isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);

// === If we have token.json, load it
if (file_exists($tokenPath)) {
    $client->setAccessToken(json_decode(file_get_contents($tokenPath), true));
}

// === If we don’t have token, handle OAuth
if (!$client->getAccessToken()) {
    if (!isset($_GET['code'])) {
        // Show Google auth link
        $authUrl = $client->createAuthUrl();
        echo "<h3>🔐 Connect to Google Drive</h3>";
        echo "<p><a href='$authUrl' target='_blank'>Click here to authorize Google Drive access</a></p>";
        echo "<form method='get'><input name='code' placeholder='Paste authorization code here' required style='width:400px;'> <button type='submit'>Submit Code</button></form>";
        exit;
    } else {
        // User returned with auth code
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            exit("❌ Error fetching token: " . htmlspecialchars($token['error_description']));
        }
        file_put_contents($tokenPath, json_encode($token));
        $client->setAccessToken($token);
    }
}

// === Step 1: Dump Database
exec("mysqldump -h $dbHost -u $dbUser -p'$dbPass' $dbName > $dbFile");

// === Step 2: Zip Website Files (excluding backups folder)
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
    foreach ($files as $file) {
        if (!$file->isDir() && strpos($file->getPathname(), 'backups') === false) {
            $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen(__DIR__) + 1));
        }
    }
    $zip->close();
}

// === Step 3: Upload to Google Drive
$drive = new Google_Service_Drive($client);

function uploadToDrive($path, $drive) {
    $file = new Google_Service_Drive_DriveFile();
    $file->setName(basename($path));
    $drive->files->create($file, [
        'data' => file_get_contents($path),
        'mimeType' => mime_content_type($path),
        'uploadType' => 'multipart'
    ]);
}

uploadToDrive($dbFile, $drive);
uploadToDrive($zipFile, $drive);

echo "<p>✅ Backup complete and uploaded to Google Drive.</p>";
