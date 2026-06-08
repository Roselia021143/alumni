<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$lineage = $id > 0 ? $studentModel->getLineage($id) : null;

if (!$lineage) {
    Session::flash('error', 'ไม่พบข้อมูลสายรหัส');
    header('Location: students.php');
    exit;
}

function studentName($student)
{
    $name = $student['first_name'] . ' ' . $student['last_name'];

    if (!empty($student['nickname'])) {
        $name .= ' (' . $student['nickname'] . ')';
    }

    return $name;
}

function renderStudentCard($student, $label)
{
    ?>
    <article class="rounded-md border border-slate-200 bg-white p-4">
        <div class="flex items-center gap-3">
            <?php echo renderStudentAvatar($student, 'h-11 w-11'); ?>
            <div>
                <p class="text-xs font-semibold text-teal-700"><?php echo h($label); ?></p>
                <h4 class="mt-1 font-semibold"><?php echo h($student['student_code']); ?> - <?php echo h(studentName($student)); ?></h4>
                <p class="mt-1 text-sm text-slate-600">รุ่น <?php echo h($student['generation']); ?></p>
            </div>
        </div>
    </article>
    <?php
}

$student = $lineage['student'];
$ancestors = $lineage['ancestors'];
$descendants = $lineage['descendants'];

ob_start();
?>
<section class="mb-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="flex items-center gap-4">
        <?php echo renderStudentAvatar($student, 'h-20 w-20'); ?>
        <div>
            <p class="text-sm font-medium text-slate-500">นักศึกษาปัจจุบัน</p>
            <h3 class="mt-1 text-2xl font-bold"><?php echo h($student['student_code']); ?> - <?php echo h(studentName($student)); ?></h3>
            <p class="mt-2 text-sm text-slate-600">
                รุ่น <?php echo h($student['generation']); ?> · <?php echo h($student['faculty']); ?> / <?php echo h($student['major']); ?>
            </p>
        </div>
    </div>
</section>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h3 class="text-lg font-semibold">พี่รหัสขึ้นไป</h3>

        <?php if (empty($ancestors)): ?>
            <p class="mt-4 text-sm text-slate-500">ยังไม่มีข้อมูลพี่รหัส</p>
        <?php endif; ?>

        <div class="mt-4 space-y-3">
            <?php foreach ($ancestors as $ancestor): ?>
                <?php renderStudentCard($ancestor, 'ชั้นที่ ' . $ancestor['line_level']); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h3 class="text-lg font-semibold">น้องรหัสลงไป</h3>

        <?php if (empty($descendants)): ?>
            <p class="mt-4 text-sm text-slate-500">ยังไม่มีข้อมูลน้องรหัส</p>
        <?php endif; ?>

        <div class="mt-4 space-y-3">
            <?php foreach ($descendants as $descendant): ?>
                <?php renderStudentCard($descendant, 'ชั้นที่ ' . $descendant['line_level']); ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="mt-6">
    <a href="students.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">กลับหน้ารายการ</a>
</div>
<?php
$content = ob_get_clean();

renderAdminLayout('สายรหัส', 'students', $content);
