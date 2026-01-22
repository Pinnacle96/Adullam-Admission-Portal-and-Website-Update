<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');
date_default_timezone_set('Africa/Lagos');

// === CONFIG ===
$dbHost = 'localhost';
$dbUser = 'u499616432_adullamn';
$dbPass = 'Rq;u54Y77#QFxx';
$dbName = 'u499616432_adullamn_cams';

$backupDir = __DIR__ . '/backups';
if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

$logFile = $backupDir . '/backup_log_' . date('Y-m-d') . '.log';
function logMessage($message) {
    global $logFile;
    $timestamp = date('[Y-m-d H:i:s]');
    file_put_contents($logFile, "$timestamp $message\n", FILE_APPEND);
}

$dbFile = "$backupDir/db_" . date('Y-m-d_H-i-s') . ".sql";
$zipFile = "$backupDir/site_" . date('Y-m-d_H-i-s') . ".zip";

logMessage("🔄 Starting backup process...");

// === STEP 1: Export Database Without exec() ===
function exportDatabase($host, $user, $pass, $dbname, $savePath) {
    $conn = new mysqli($host, $user, $pass, $dbname);
    if ($conn->connect_error) return false;

    $tables = [];
    $res = $conn->query("SHOW TABLES");
    while ($row = $res->fetch_array()) $tables[] = $row[0];

    $sqlScript = "";
    foreach ($tables as $table) {
        $res = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $res->fetch_row();
        $sqlScript .= "\n\n" . $row[1] . ";\n\n";

        $res = $conn->query("SELECT * FROM `$table`");
        while ($row = $res->fetch_assoc()) {
            $columns = array_map(fn($v) => "`$v`", array_keys($row));
            $values = array_map(function ($v) use ($conn) {
                return "'" . $conn->real_escape_string((string)($v ?? '')) . "'";
            }, array_values($row));
            $sqlScript .= "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ");\n";
        }
    }

    file_put_contents($savePath, $sqlScript);
    return true;
}

$dbExported = exportDatabase($dbHost, $dbUser, $dbPass, $dbName, $dbFile);
if ($dbExported) {
    logMessage("✅ Database export completed successfully.");
} else {
    logMessage("❌ Database export failed.");
}

// === STEP 2: Zip Website Files ===
$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
    foreach ($files as $file) {
        $realPath = $file->getPathname();
        if (!$file->isDir() && strpos($realPath, 'backups') === false) {
            $zip->addFile($realPath, substr($realPath, strlen(__DIR__) + 1));
        }
    }
    $zip->close();
    logMessage("✅ Website files zipped successfully: $zipFile");
} else {
    logMessage("❌ Failed to create ZIP archive: $zipFile");
}

logMessage("✅ Backup process completed.\n");

echo "✅ Backup created:<br>• <code>$dbFile</code><br>• <code>$zipFile</code><br>📁 Log file: <code>$logFile</code>";
?>
