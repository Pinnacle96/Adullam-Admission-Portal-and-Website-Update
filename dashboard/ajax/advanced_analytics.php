<?php
require_once '../db.php';

header('Content-Type: application/json');

try {
    // 0. Fetch Current Active Cohort or use GET parameter
    if (isset($_GET['cohort']) && !empty($_GET['cohort'])) {
        $activeCohort = $_GET['cohort'];
    } else {
        $cohortStmt = $pdo->query("SELECT value FROM settings WHERE `key` = 'current_cohort'");
        $activeCohort = $cohortStmt->fetchColumn() ?: 'January 2026';
    }

    $response = [];

    // 1. Gender Distribution
    $stmt = $pdo->prepare("
        SELECT gender, COUNT(*) as count 
        FROM application_details ad 
        JOIN applications a ON ad.user_id = a.user_id 
        WHERE a.cohort = ? 
        GROUP BY gender
    ");
    $stmt->execute([$activeCohort]);
    $response['gender_distribution'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Mode of Study Distribution
    $stmt = $pdo->prepare("
        SELECT mode_of_study, COUNT(*) as count 
        FROM application_details ad 
        JOIN applications a ON ad.user_id = a.user_id 
        WHERE a.cohort = ? 
        GROUP BY mode_of_study
    ");
    $stmt->execute([$activeCohort]);
    $response['mode_distribution'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 3. Top 5 Countries
    $stmt = $pdo->prepare("
        SELECT res_country, COUNT(*) as count 
        FROM application_details ad 
        JOIN applications a ON ad.user_id = a.user_id 
        WHERE a.cohort = ? 
        GROUP BY res_country 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stmt->execute([$activeCohort]);
    $response['country_distribution'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4. Age Distribution (Ranges)
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 20 THEN 'Under 20'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 20 AND 29 THEN '20-29'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 30 AND 39 THEN '30-39'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 40 AND 49 THEN '40-49'
                WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) >= 50 THEN '50+'
                ELSE 'Unknown'
            END as age_range,
            COUNT(*) as count
        FROM application_details ad
        JOIN applications a ON ad.user_id = a.user_id
        WHERE a.cohort = ?
        GROUP BY age_range
        ORDER BY age_range
    ");
    $stmt->execute([$activeCohort]);
    $response['age_distribution'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 5. Program Distribution
    $stmt = $pdo->prepare("
        SELECT program, COUNT(*) as count
        FROM application_details ad
        JOIN applications a ON ad.user_id = a.user_id
        WHERE a.cohort = ?
        GROUP BY program
    ");
    $stmt->execute([$activeCohort]);
    $response['program_distribution'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>