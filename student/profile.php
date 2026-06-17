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

$isOwner = (int) $ownerStudentId === (int) $targetStudentId;
$fields = [
    ['รหัสนักศึกษา', 'student_code', 'student_code_visible'],
    ['ชื่อ', 'first_name', null],
    ['นามสกุล', 'last_name', null],
    ['ชื่อเล่น', 'nickname', null],
    ['ปีการศึกษา', 'generation', 'generation_visible'],
    ['คณะ', 'faculty', null],
    ['สาขา', 'major', null],
    ['เบอร์โทร', 'phone', 'phone_visible'],
    ['Facebook', 'facebook', 'facebook_visible'],
    ['Instagram', 'instagram', 'instagram_visible'],
    ['Line ID', 'line_id_contact', 'line_id_contact_visible'],
];

if (!$isOwner && isset($student['profile_image_visible']) && (int) $student['profile_image_visible'] === 0) {
    $student['profile_image'] = '';
}

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
        <?php foreach ($fields as $fieldConfig): ?>
            <?php
            $label = $fieldConfig[0];
            $field = $fieldConfig[1];
            $visibleField = $fieldConfig[2];

            if (!$isOwner && $visibleField !== null && empty($student[$visibleField])) {
                continue;
            }
            ?>
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
