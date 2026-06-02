<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Upload Test</h2>";
    echo "<pre>";
    echo "FILES data:\n";
    print_r($_FILES);
    echo "\nPOST data:\n";
    print_r($_POST);
    echo "</pre>";

    if (isset($_FILES['testfile']) && $_FILES['testfile']['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . '/uploads/documents/';
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        $targetFile = $targetDir . basename($_FILES['testfile']['name']);
        if (move_uploaded_file($_FILES['testfile']['tmp_name'], $targetFile)) {
            echo "<p style='color: green;'>✅ Upload successful! File saved to: " . htmlspecialchars($targetFile) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Upload failed!</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Upload</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        form { border: 1px solid #ddd; padding: 2rem; border-radius: 8px; }
        input[type="file"] { margin: 1rem 0; }
        button { background: #7c3aed; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Simple Upload Test</h1>

    <form method="POST" enctype="multipart/form-data">
        <label for="testfile">Select a small file to upload:</label>
        <input type="file" id="testfile" name="testfile" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
