<?php
header('Content-Type: application/json');
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$semester = isset($payload['semester']) ? trim((string)$payload['semester']) : '';

$allowed = ['First Semester', 'Second Semester', '__all__'];
if (!in_array($semester, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid semester']);
    exit;
}

try {
    $relaxArchiveTable = function (string $archiveTable, string $sourceTable) use ($pdo) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `$archiveTable` LIKE `$sourceTable`");

        try {
            $pdo->exec("ALTER TABLE `$archiveTable` DROP PRIMARY KEY");
        } catch (Exception $e) {
        }

        try {
            $idxStmt = $pdo->query("SHOW INDEX FROM `$archiveTable`");
            $idxRows = $idxStmt ? $idxStmt->fetchAll(PDO::FETCH_ASSOC) : [];
            $uniqueIndexNames = [];
            foreach ($idxRows as $row) {
                if (($row['Key_name'] ?? '') === 'PRIMARY') {
                    continue;
                }
                if (isset($row['Non_unique']) && (string)$row['Non_unique'] === '0') {
                    $uniqueIndexNames[(string)$row['Key_name']] = true;
                }
            }
            foreach (array_keys($uniqueIndexNames) as $idxName) {
                try {
                    $pdo->exec("ALTER TABLE `$archiveTable` DROP INDEX `$idxName`");
                } catch (Exception $e) {
                }
            }
        } catch (Exception $e) {
        }
    };

    $relaxArchiveTable('hostel_registrations_archive', 'hostel_registrations');
    $relaxArchiveTable('hostel_allocations_archive', 'hostel_allocations');

    $pdo->beginTransaction();

    if ($semester === '__all__') {
        $insAlloc = $pdo->exec("INSERT INTO hostel_allocations_archive SELECT * FROM hostel_allocations");
        $delAlloc = $pdo->exec("DELETE FROM hostel_allocations");

        $insReg = $pdo->exec("INSERT INTO hostel_registrations_archive SELECT * FROM hostel_registrations");
        $delReg = $pdo->exec("DELETE FROM hostel_registrations");
    } else {
        $insAllocStmt = $pdo->prepare("INSERT INTO hostel_allocations_archive SELECT * FROM hostel_allocations WHERE semester = ?");
        $insAllocStmt->execute([$semester]);
        $insAlloc = $insAllocStmt->rowCount();

        $delAllocStmt = $pdo->prepare("DELETE FROM hostel_allocations WHERE semester = ?");
        $delAllocStmt->execute([$semester]);
        $delAlloc = $delAllocStmt->rowCount();

        $insRegStmt = $pdo->prepare("INSERT INTO hostel_registrations_archive SELECT * FROM hostel_registrations WHERE semester = ?");
        $insRegStmt->execute([$semester]);
        $insReg = $insRegStmt->rowCount();

        $delRegStmt = $pdo->prepare("DELETE FROM hostel_registrations WHERE semester = ?");
        $delRegStmt->execute([$semester]);
        $delReg = $delRegStmt->rowCount();
    }

    $pdo->commit();

    $label = $semester === '__all__' ? 'all semesters' : $semester;
    echo json_encode([
        'success' => true,
        'message' => "Archived hostel registrations ({$insReg}) and allocations ({$insAlloc}) for {$label}."
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    // Log to a file we can actually read from the server easily
    file_put_contents(__DIR__ . '/clear_error.log', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    // Also return the raw error message to the frontend just for debugging (temporarily)
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
