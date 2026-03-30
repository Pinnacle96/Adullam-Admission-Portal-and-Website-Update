<?php
/**
 * Check if Family-House is full for a semester
 * (optionally per-gender). Returns true when no bed is left.
 */
function getSettingValue(PDO $pdo, string $key, ?string $default = null): ?string
{
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    if ($val === false || $val === null || $val === '') {
        return $default;
    }
    return (string)$val;
}

function hostelIsFull(PDO $pdo, string $semester, ?string $gender = null): bool
{
    /* ── total capacity ────────────────────────────────── */
    $capSql  = "SELECT COALESCE(SUM(capacity),0)
                FROM hostel_rooms
                WHERE active = 1 AND semester = ?";
    $capBind = [$semester];

    if ($gender) {
        $capSql .= " AND gender = ?";
        $capBind[] = $gender;
    }

    $capStmt = $pdo->prepare($capSql);
    $capStmt->execute($capBind);
    $totalBeds = (int)$capStmt->fetchColumn();      // total available

    /* ── beds already taken (pending + approved) ──────── */
    $occSql  = "SELECT COUNT(*)
                FROM hostel_registrations
                WHERE semester = ? AND status <> 'rejected'";
    $occBind = [$semester];

    if ($gender) {
        $occSql .= " AND gender = ?";
        $occBind[] = $gender;
    }

    $occStmt = $pdo->prepare($occSql);
    $occStmt->execute($occBind);
    $takenBeds = (int)$occStmt->fetchColumn();      // already booked

    return $takenBeds >= $totalBeds && $totalBeds > 0;
}
?>
