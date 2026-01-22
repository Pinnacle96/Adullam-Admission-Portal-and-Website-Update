<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

// 🧠 Fetch application data
$app = $pdo->prepare("SELECT admission_no, status, submitted FROM applications WHERE user_id = ?");
$app->execute([$user_id]);
$appData = $app->fetch();
$addmissionNo = $appData['admission_no'] ?? '';
$status = $appData['status'] ?? 'incomplete';
$isSubmitted = $appData['submitted'] ?? 0;

$detail = $pdo->prepare("SELECT program, ma_focus, mode_of_study FROM application_details WHERE user_id = ?");
$detail->execute([$user_id]);
$student = $detail->fetch();
$program = $student['program'] ?? '';
$focus = $student['ma_focus'] ?? '';
$mode = $student['mode_of_study'] ?? 'online';

$docsStmt = $pdo->prepare("SELECT passport, transcript FROM application_documents WHERE user_id = ?");
$docsStmt->execute([$user_id]);
$docs = $docsStmt->fetch();
$passport = $docs['passport'] ?? '';
$transcriptUploaded = ($docs['transcript'] ?? '') !== '' ? 'Yes' : 'No';

$isAdmitted = $status === 'admitted';
$admissionLetterPath = "letters/admission_letters/{$user_id}.pdf";
