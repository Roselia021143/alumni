<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

header('Content-Type: application/json; charset=UTF-8');

$studentCode = isset($_GET['code']) ? trim($_GET['code']) : '';

if ($studentCode === '') {
    echo json_encode([
        'success' => false,
        'message' => 'กรุณากรอกรหัสนักศึกษา',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$studentModel = new Student($conn);
$student = $studentModel->findByCode($studentCode);

if (!$student) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบรหัสนักศึกษานี้ในระบบ',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((int) $student['id'] === Session::studentId()) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถใช้รหัสนักศึกษาของตัวเองได้',
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
