<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

header('Content-Type: application/json; charset=UTF-8');

$studentModel = new Student($conn);
$studentCode = isset($_GET['code']) ? trim($_GET['code']) : '';
$studentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$student = null;

if ($studentCode !== '') {
    $student = $studentModel->findByCode($studentCode);
} elseif ($studentId > 0) {
    $student = $studentModel->find($studentId);
}

if (!$student) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบข้อมูลนักศึกษาในระบบ',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$name = trim((string) $student['first_name'] . ' ' . (string) $student['last_name']);

if (!empty($student['nickname'])) {
    $name .= ' (' . $student['nickname'] . ')';
}

echo json_encode([
    'success' => true,
    'student' => [
        'student_code' => $student['student_code'],
        'name' => $name !== '' ? $name : 'ยังไม่ได้กรอกชื่อ',
        'generation' => $student['generation'],
    ],
], JSON_UNESCAPED_UNICODE);
