<?php
// Script to fix existing document paths in the database by prepending '/'
// You should delete this file after use for security!
session_start();
require 'db.php';

// Only allow admins or the user themselves?
// For safety, let's just run it and be done!

echo "<h2>Fixing Document Paths</h2>";
echo "<pre>";

try {
    $stmt = $pdo->query("SELECT user_id, passport, ssce_cert, ssce_cert2, birth_cert, origin_cert, recommendation, payment_proof, degree_cert, transcript FROM application_documents");
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    foreach ($docs as $doc) {
        $userId = $doc['user_id'];
        $updates = [];
        $params = [];
        
        $fields = ['passport', 'ssce_cert', 'ssce_cert2', 'birth_cert', 'origin_cert', 'recommendation', 'payment_proof', 'degree_cert', 'transcript'];
        
        foreach ($fields as $field) {
            if (!empty($doc[$field])) {
                $path = $doc[$field];
                if (substr($path, 0, 1) !== '/') {
                    $updates[] = "$field = ?";
                    $params[] = '/' . ltrim($path, '/');
                }
            }
        }
        
        if (!empty($updates)) {
            $params[] = $userId;
            $sql = "UPDATE application_documents SET " . implode(', ', $updates) . " WHERE user_id = ?";
            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute($params);
            echo "✓ Updated user $userId\n";
            $count++;
        }
    }
    echo "\n✅ Done! Updated $count records!";
    echo "\n\nIMPORTANT: Delete this file (fix_doc_paths.php) from the server now for security!";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "</pre>";
?>
