<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h2>PHP & Server Debug Info</h2>";
echo "PHP is working!<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Public HTML path: " . dirname(__DIR__) . "<br>";
echo "Upload directory check: " . (file_exists(__DIR__ . "/uploads/documents") ? "exists" : "does NOT exist") . "<br>";
if (!file_exists(__DIR__ . "/uploads/documents")) {
    mkdir(__DIR__ . "/uploads/documents", 0755, true);
    echo "Created uploads/documents directory!<br>";
}
echo "Upload directory now: " . (file_exists(__DIR__ . "/uploads/documents") ? "exists" : "still does NOT exist") . "<br>";

// Check PHP upload limits
echo "<h3>PHP Upload Limits</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . " seconds<br>";
echo "max_input_time: " . ini_get('max_input_time') . " seconds<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

// Test database connection
require 'db.php';
echo "<h3>Database</h3>";
echo "Database connected!<br>";
echo "DB file path: " . __DIR__ . "/db.php<br>";

// Check application_documents table structure
echo "<h3>application_documents table structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE application_documents");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
} catch (Exception $e) {
    echo "Error checking table: " . $e->getMessage() . "<br>";
}

echo "<h3>All good!</h3>";
?>