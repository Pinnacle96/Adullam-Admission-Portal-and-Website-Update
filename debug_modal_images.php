<?php
/**
 * Modal Image Debug & Diagnostic Script
 * Run this on your live server to identify modal image issues
 * Access: https://yourdomain.com/debug_modal_images.php
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🔍 Modal Image Diagnostic Report</h1>";
echo "<hr>";

// Check 1: Assets folder exists
echo "<h2>1. File System Check</h2>";
$assetPaths = [
    'assets/img' => __DIR__ . '/assets/img',
    'assets/img/modal' => __DIR__ . '/assets/img/modal',
];

foreach ($assetPaths as $name => $path) {
    $exists = is_dir($path);
    $status = $exists ? "✅ EXISTS" : "❌ MISSING";
    echo "<p><strong>$name</strong>: $status</p>";
    
    if ($exists) {
        $files = glob($path . '/*.*');
        echo "<p style='margin-left:20px'>Files: " . count($files) . "</p>";
        if (count($files) > 0) {
            echo "<ul style='margin-left:20px'>";
            foreach (array_slice($files, 0, 5) as $file) {
                echo "<li>" . basename($file) . "</li>";
            }
            if (count($files) > 5) echo "<li>... and " . (count($files) - 5) . " more</li>";
            echo "</ul>";
        }
    }
}

// Check 2: Database modal content
echo "<h2>2. Database Modal Content</h2>";
include 'includes/dbconnection.php';

if (isset($con)) {
    try {
        $stmt = $con->prepare("SELECT PageTitle, PageDescription, Email FROM tblpage WHERE PageType = 'home_modal'");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo "<p><strong>Title:</strong> " . htmlspecialchars($row['PageTitle']) . "</p>";
            echo "<p><strong>Status:</strong> " . ($row['Email'] === 'active' ? "✅ ACTIVE" : "❌ INACTIVE") . "</p>";
            
            echo "<h3>Image URLs in Description:</h3>";
            $content = $row['PageDescription'];
            
            // Find all image tags
            if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
                echo "<ul>";
                foreach ($matches[1] as $url) {
                    $isAbsolute = filter_var($url, FILTER_VALIDATE_URL);
                    $type = $isAbsolute ? "🌐 ABSOLUTE" : "📁 RELATIVE";
                    echo "<li>$type: <code>" . htmlspecialchars($url) . "</code></li>";
                }
                echo "</ul>";
            } else {
                echo "<p>⚠️ No image tags found in modal content</p>";
            }
            
            echo "<h3>Raw Content Preview:</h3>";
            echo "<pre style='background:#f0f0f0;padding:10px;overflow:auto;max-height:300px'>" . 
                 htmlspecialchars(substr($content, 0, 500)) . 
                 "</pre>";
        } else {
            echo "<p>❌ No modal configuration found in database</p>";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ Database connection failed</p>";
}

// Check 3: CI/CD Info
echo "<h2>3. CI/CD Deployment Info</h2>";
echo "<p><strong>Current Path:</strong> <code>" . __DIR__ . "</code></p>";
echo "<p><strong>Script Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check 4: Recommended fixes
echo "<h2>4. Recommended Fixes</h2>";
echo "<ul>";
echo "<li>✅ Store modal images in <strong>assets/img/modal/</strong> subfolder</li>";
echo "<li>✅ Use relative paths in database: <strong>assets/img/modal/filename.jpg</strong></li>";
echo "<li>✅ Use WordPress-style placeholders: <strong>{{SITE_URL}}/assets/img/modal/</strong></li>";
echo "<li>✅ Add assets/img to NO-EXCLUDE list in CI/CD config</li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Remove this file after debugging: <code>rm debug_modal_images.php</code></small></p>";
?>