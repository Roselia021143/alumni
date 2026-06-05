<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: students.php');
    exit;
}

$studentModel = new Student($conn);
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

try {
    if ($id > 0) {
        $studentModel->update($id, $_POST);
        Session::flash('success', 'แก้ไขข้อมูลนักศึกษาเรียบร้อยแล้ว');
    } else {
        $studentModel->create($_POST, true);
        Session::flash('success', 'เพิ่มข้อมูลนักศึกษาและสร้างบัญชีผู้ใช้เริ่มต้นเรียบร้อยแล้ว');
    }
} catch (mysqli_sql_exception $exception) {
    Session::flash('error', 'ไม่สามารถบันทึกข้อมูลได้ อาจมีรหัสนักศึกษาซ้ำ');
}

header('Location: students.php');
exit;
