<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$studentId = Session::studentId();
$lineage = $studentModel->getLineagePreview($studentId, 5);

if (!$lineage) {
    Session::logout();
    header('Location: login.php');
    exit;
}

$student = $lineage['student'];
$ancestors = $lineage['ancestors'];
$descendants = $lineage['descendants'];

function studentDisplayName($student)
{
    $name = $student['first_name'] . ' ' . $student['last_name'];

    if (!empty($student['nickname'])) {
        $name .= ' (' . $student['nickname'] . ')';
    }

    return $name;
}

function renderLinePreviewItem($item)
{
    if (isset($item['profile_image_visible']) && (int) $item['profile_image_visible'] === 0) {
        $item['profile_image'] = '';
    }
    ?>
    <a href="profile.php?id=<?php echo (int) $item['id']; ?>" class="block rounded-md border border-slate-200 p-4 hover:bg-slate-50">
        <div class="flex items-center gap-3">
            <?php echo renderStudentAvatar($item, 'h-11 w-11'); ?>
            <div>
                <p class="text-xs font-semibold text-teal-700">ชั้นที่ <?php echo h($item['line_level']); ?></p>
                <h4 class="mt-1 font-semibold"><?php echo h(studentDisplayName($item)); ?></h4>
                <p class="mt-1 text-sm text-slate-600">ปีการศึกษา <?php echo h($item['generation']); ?></p>
            </div>
        </div>
    </a>
    <?php
}

ob_start();
?>
<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="flex items-center gap-4">
            <?php echo renderStudentAvatar($student, 'h-20 w-20'); ?>
            <div>
                <p class="text-sm font-medium text-slate-500">ข้อมูลนักศึกษา</p>
                <h2 class="mt-1 text-2xl font-bold"><?php echo h($student['student_code']); ?> - <?php echo h(studentDisplayName($student)); ?></h2>
                <p class="mt-2 text-sm text-slate-600">รุ่น <?php echo h($student['generation']); ?> · <?php echo h($student['faculty']); ?> / <?php echo h($student['major']); ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="tree.php" class="rounded-md border border-teal-700 px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">ดูแบบกิ่งไม้</a>
            <a href="profile.php?id=<?php echo (int) $student['id']; ?>" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">ดูข้อมูลทั้งหมด</a>
        </div>
    </div>

    <dl class="mt-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-md bg-slate-50 p-4">
            <dt class="text-xs font-semibold text-slate-500">เบอร์โทร</dt>
            <dd class="mt-1 font-medium"><?php echo h($student['phone']); ?></dd>
        </div>
        <div class="rounded-md bg-slate-50 p-4">
            <dt class="text-xs font-semibold text-slate-500">Facebook</dt>
            <dd class="mt-1 font-medium"><?php echo h($student['facebook']); ?></dd>
        </div>
        <div class="rounded-md bg-slate-50 p-4">
            <dt class="text-xs font-semibold text-slate-500">Line ID</dt>
            <dd class="mt-1 font-medium"><?php echo h($student['line_id_contact']); ?></dd>
        </div>
    </dl>
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold">พี่รหัสขึ้นไป</h3>
            <a href="line.php?direction=up" class="text-sm font-semibold text-teal-700 hover:underline">ดูทั้งหมด</a>
        </div>
        <div class="mt-4 space-y-3">
            <?php if (empty($ancestors)): ?>
                <p class="text-sm text-slate-500">ยังไม่มีข้อมูลพี่รหัส</p>
            <?php endif; ?>
            <?php foreach ($ancestors as $item): ?>
                <?php renderLinePreviewItem($item); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold">น้องรหัสลงไป</h3>
            <a href="line.php?direction=down" class="text-sm font-semibold text-teal-700 hover:underline">ดูทั้งหมด</a>
        </div>
        <div class="mt-4 space-y-3">
            <?php if (empty($descendants)): ?>
                <p class="text-sm text-slate-500">ยังไม่มีข้อมูลน้องรหัส</p>
            <?php endif; ?>
            <?php foreach ($descendants as $item): ?>
                <?php renderLinePreviewItem($item); ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();

renderStudentLayout('หน้าแรก', $content);
