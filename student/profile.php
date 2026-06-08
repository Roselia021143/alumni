<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$ownerStudentId = Session::studentId();
$targetStudentId = isset($_GET['id']) ? (int) $_GET['id'] : $ownerStudentId;

if (!$studentModel->isInLineage($ownerStudentId, $targetStudentId)) {
    header('Location: dashboard.php');
    exit;
}

$student = $studentModel->find($targetStudentId);

if (!$student) {
    header('Location: dashboard.php');
    exit;
}

$fields = [
    'รหัสนักศึกษา' => 'student_code',
    'ชื่อ' => 'first_name',
    'นามสกุล' => 'last_name',
    'ชื่อเล่น' => 'nickname',
    'ปีการศึกษา' => 'generation',
    'คณะ' => 'faculty',
    'สาขา' => 'major',
    'เบอร์โทร' => 'phone',
    'Facebook' => 'facebook',
    'Instagram' => 'instagram',
    'Line ID' => 'line_id_contact',
];

ob_start();
?>
<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-4">
            <?php echo renderStudentAvatar($student, 'h-20 w-20'); ?>
            <div>
                <p class="text-sm font-medium text-slate-500">ข้อมูลแบบอ่านอย่างเดียว</p>
                <h2 class="mt-1 text-2xl font-bold"><?php echo h($student['first_name'] . ' ' . $student['last_name']); ?></h2>
            </div>
        </div>
        <a href="dashboard.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">กลับหน้าแรก</a>
    </div>

    <dl class="grid gap-4 md:grid-cols-2">
        <?php foreach ($fields as $label => $field): ?>
            <div class="rounded-md bg-slate-50 p-4">
                <dt class="text-xs font-semibold text-slate-500"><?php echo h($label); ?></dt>
                <dd class="mt-1 font-medium"><?php echo h($student[$field]); ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>
<?php
$content = ob_get_clean();

renderStudentLayout('ข้อมูลนักศึกษา', $content);
