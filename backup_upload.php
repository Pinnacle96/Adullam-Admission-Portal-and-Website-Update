<?php
ini_set('memory_limit', '1024M');
set_time_limit(0);
date_default_timezone_set('Africa/Lagos');

require_once __DIR__ . '/google-api-client/vendor/autoload.php';

$tokenPath = __DIR__ . '/token.json';
$credPath = __DIR__ . '/client_secret_153249119205-45715c9j39g6419k2pl3pn0h6b1gnufc.apps.googleusercontent.com.json';

// === Log Setup ===
$logDir = __DIR__ . '/backups';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/upload_log_' . date('Y-m-d') . '.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, "$timestamp $message\n", FILE_APPEND);
}

// === Google Client Auth ===
$client = new Google_Client();
$client->setAuthConfig($credPath);
$client->addScope(Google_Service_Drive::DRIVE_FILE);
$client->setAccessType('offline');

if (file_exists($tokenPath)) {
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    // Refresh token if expired
    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
            logMessage("🔄 Access token refreshed.");
        } else {
            $msg = "❌ Refresh token missing. Re-authentication required.";
            logMessage($msg);
            exit($msg);
        }
    }
} else {
    $msg = "❌ Token missing. Run initial auth first.";
    logMessage($msg);
    exit($msg);
}

$drive = new Google_Service_Drive($client);

// === Upload Function ===
function uploadToDrive($path, $drive) {
    if (!file_exists($path)) {
        logMessage("File not found: $path");
        return;
    }

    $fileSize = filesize($path);
    $fileName = basename($path);
    $mimeType = mime_content_type($path);

    $fileMeta = new Google_Service_Drive_DriveFile([
        'name' => $fileName
    ]);

    try {
        $chunkSizeBytes = 5 * 1024 * 1024; // 5MB
        $client = $drive->getClient();
        $client->setDefer(true);

        $request = $drive->files->create($fileMeta, [
            'mimeType' => $mimeType
        ]);

        $media = new Google_Http_MediaFileUpload(
            $client,
            $request,
            $mimeType,
            null,
            true,
            $chunkSizeBytes
        );
        $media->setFileSize($fileSize);

        $handle = fopen($path, "rb");
        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSizeBytes);
            $media->nextChunk($chunk);
        }
        fclose($handle);

        $client->setDefer(false);

        logMessage("✅ Uploaded successfully (resumable): $path");
        echo "✅ Uploaded: " . basename($path) . "<br>";
    } catch (Exception $e) {
        logMessage("❌ Failed to upload $path. Error: " . $e->getMessage());
        echo "❌ Error uploading: " . basename($path) . "<br>";
    }
}

// === Start Upload Process ===
logMessage("Starting upload to Google Drive...");

$backupFiles = glob(__DIR__ . "/backups/*.{sql,zip}", GLOB_BRACE);
if (empty($backupFiles)) {
    logMessage("No backup files found.");
    echo "⚠️ No backup files to upload.<br>";
} else {
    // Find latest modified file
    $latestFile = array_reduce($backupFiles, function ($latest, $current) {
        return (filemtime($current) > filemtime($latest)) ? $current : $latest;
    }, $backupFiles[0]);

    logMessage("Uploading latest backup: $latestFile");
    uploadToDrive($latestFile, $drive);

    // === Move old backups ===
    $archiveDir = __DIR__ . '/backups/archived';
    if (!is_dir($archiveDir)) mkdir($archiveDir, 0755, true);

    foreach ($backupFiles as $file) {
        if ($file !== $latestFile) {
            $destination = $archiveDir . '/' . basename($file);
            if (rename($file, $destination)) {
                logMessage("Moved old backup to archive: $file");
            } else {
                logMessage("❌ Failed to move file: $file");
            }
        }
    }
}

logMessage("Upload process completed.");
echo "<br>📁 Log saved to: <code>$logFile</code>";
?>
