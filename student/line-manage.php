<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/SystemSetting.php';

$studentModel = new Student($conn);
$setting = new SystemSetting($conn);
$studentId = Session::studentId();
$student = $studentModel->find($studentId);
$currentAcademicYear = $setting->currentAcademicYear();
$yearLevel = $student ? $studentModel->studentYearLevel($student['student_code'], $currentAcademicYear) : 0;
$canManageChild = $student ? $studentModel->canManageChildCode($student['student_code'], $currentAcademicYear) : false;
$success = null;
$error = null;

if (!$student) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['parent_student_code']) && trim($_POST['parent_student_code']) !== '') {
            $studentModel->linkParentByCode($studentId, $_POST['parent_student_code']);
            $success = 'บันทึกพี่รหัสเรียบร้อยแล้ว ระบบจะแสดงคุณเป็นน้องรหัสในฝั่งพี่รหัสอัตโนมัติ';
        }

        if (isset($_POST['child_student_code']) && trim($_POST['child_student_code']) !== '') {
            if (!$canManageChild) {
                throw new RuntimeException('นักศึกษาปี 1 สามารถกรอกได้เฉพาะพี่รหัส');
            }

            $studentModel->linkChildByCode($studentId, $_POST['child_student_code']);
            $success = 'บันทึกน้องรหัสเรียบร้อยแล้ว ระบบจะแสดงน้องรหัสในสายของคุณอัตโนมัติ';
        }
    } catch (Exception $exception) {
        $error = $exception->getMessage();
    }
}

$lineage = $studentModel->getLineage($studentId);
$currentParent = null;
$directChildren = [];

if ($lineage) {
    foreach ($lineage['ancestors'] as $ancestor) {
        if ((int) $ancestor['line_level'] === 1) {
            $currentParent = $ancestor;
            break;
        }
    }

    foreach ($lineage['descendants'] as $descendant) {
        if ((int) $descendant['line_level'] === 1) {
            $directChildren[] = $descendant;
        }
    }
}

function lineManageName($student)
{
    $name = trim((string) $student['first_name'] . ' ' . (string) $student['last_name']);

    if (!empty($student['nickname'])) {
        $name .= ' (' . $student['nickname'] . ')';
    }

    return $name !== '' ? $name : $student['student_code'];
}

ob_start();
?>
<?php if ($success): ?>
    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo h($error); ?></div>
<?php endif; ?>

<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-lg font-semibold">ผูกสายรหัส</h2>
    <p class="mt-2 text-sm text-slate-600">
        รหัส <?php echo h($student['student_code']); ?> เข้าปีการศึกษา <?php echo h($studentModel->admissionYearFromCode($student['student_code'])); ?>,
        ปีการศึกษาปัจจุบัน <?php echo h($currentAcademicYear); ?>, คำนวณเป็นปี <?php echo h($yearLevel); ?>
    </p>

    <form method="post" class="mt-6 grid gap-5 md:grid-cols-2">
        <div>
            <label for="parent_student_code" class="mb-2 block text-sm font-medium">รหัสนักศึกษาของพี่รหัส</label>
            <input id="parent_student_code" name="parent_student_code" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="เช่น 65113532012">
        </div>

        <div>
            <label for="child_student_code" class="mb-2 block text-sm font-medium">รหัสนักศึกษาของน้องรหัส</label>
            <input id="child_student_code" name="child_student_code" <?php echo $canManageChild ? '' : 'disabled'; ?> class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm <?php echo $canManageChild ? '' : 'bg-slate-100 text-slate-400'; ?>" placeholder="กรอกได้ตั้งแต่ปี 2 ขึ้นไป">
            <?php if (!$canManageChild): ?>
                <p class="mt-2 text-xs text-slate-500">ปี 1 สามารถกรอกได้เฉพาะพี่รหัส</p>
            <?php endif; ?>
        </div>

        <div class="md:col-span-2 flex justify-end">
            <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">บันทึกสายรหัส</button>
        </div>
    </form>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="rounded-md bg-slate-50 p-4">
            <p class="text-xs font-semibold text-teal-700">พี่รหัสปัจจุบัน</p>
            <?php if ($currentParent): ?>
                <p class="mt-2 font-semibold"><?php echo h($currentParent['student_code']); ?> - <?php echo h(lineManageName($currentParent)); ?></p>
                <p class="mt-1 text-sm text-slate-600">ข้อมูลนี้ถูก sync จากค่า parent_student_id ของคุณ</p>
            <?php else: ?>
                <p class="mt-2 text-sm text-slate-500">ยังไม่ได้ผูกพี่รหัส</p>
            <?php endif; ?>
        </div>

        <div class="rounded-md bg-slate-50 p-4">
            <p class="text-xs font-semibold text-teal-700">น้องรหัสที่ sync แล้ว</p>
            <?php if (empty($directChildren)): ?>
                <p class="mt-2 text-sm text-slate-500">ยังไม่มีน้องรหัสที่ผูกมายังคุณ</p>
            <?php else: ?>
                <div class="mt-3 space-y-2">
                    <?php foreach ($directChildren as $child): ?>
                        <div class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <span class="font-semibold"><?php echo h($child['student_code']); ?></span>
                            <span class="text-slate-600">- <?php echo h(lineManageName($child)); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();

renderStudentLayout('ผูกสายรหัส', $content);
