<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    try {
        if ($action === 'set_parent') {
            $targetStudentId = isset($_POST['target_student_id']) ? (int) $_POST['target_student_id'] : 0;
            $parentStudentCode = isset($_POST['parent_student_code']) ? $_POST['parent_student_code'] : '';
            $studentModel->adminSetParentByCode($targetStudentId, $parentStudentCode);
            Session::flash('success', 'แก้ไขพี่รหัสเรียบร้อยแล้ว');
        } elseif ($action === 'add_child') {
            $owner = $studentModel->find($id);
            $childCode = isset($_POST['child_student_code']) ? trim($_POST['child_student_code']) : '';
            $child = $studentModel->findByCode($childCode);

            if (!$owner || !$child) {
                throw new RuntimeException('ไม่พบรหัสนักศึกษาของน้องรหัสในระบบ');
            }

            $studentModel->adminSetParentByCode((int) $child['id'], $owner['student_code']);
            Session::flash('success', 'เพิ่มน้องรหัสเข้าสู่สายเรียบร้อยแล้ว');
        } elseif ($action === 'unlink') {
            $targetStudentId = isset($_POST['target_student_id']) ? (int) $_POST['target_student_id'] : 0;
            $studentModel->unlinkParent($targetStudentId);
            Session::flash('success', 'ถอดนักศึกษาออกจากความสัมพันธ์เดิมเรียบร้อยแล้ว');
        }
    } catch (Exception $exception) {
        Session::flash('error', $exception->getMessage());
    }

    header('Location: student-line.php?id=' . $id);
    exit;
}

$lineage = $id > 0 ? $studentModel->getLineage($id) : null;

if (!$lineage) {
    Session::flash('error', 'ไม่พบข้อมูลสายรหัส');
    header('Location: students.php');
    exit;
}

function adminLineStudentName($student)
{
    $name = trim((string) $student['first_name'] . ' ' . (string) $student['last_name']);

    if (!empty($student['nickname'])) {
        $name .= ' (' . $student['nickname'] . ')';
    }

    return $name !== '' ? $name : 'ยังไม่ได้กรอกชื่อ';
}

function renderAdminLineCard($student, $label, $returnStudentId)
{
    $hasParent = !empty($student['parent_student_id']);
    $currentParentCode = isset($student['parent_student_code']) ? $student['parent_student_code'] : '';
    ?>
    <article class="admin-line-card">
        <div class="admin-line-card-main">
            <?php echo renderStudentAvatar($student, 'h-12 w-12'); ?>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-teal-700"><?php echo h($label); ?></p>
                <h4 class="mt-1 font-semibold"><?php echo h($student['student_code']); ?> - <?php echo h(adminLineStudentName($student)); ?></h4>
                <p class="mt-1 text-sm text-slate-600">รุ่น <?php echo h($student['generation']); ?></p>
            </div>
        </div>

        <div class="admin-line-card-actions">
            <details class="admin-line-editor">
                <summary>แก้ไขสาย</summary>
                <form method="post" action="student-line.php?id=<?php echo (int) $returnStudentId; ?>" class="admin-line-action-form admin-line-inline-form" data-action-label="เปลี่ยนพี่รหัสของนักศึกษา">
                    <input type="hidden" name="action" value="set_parent">
                    <input type="hidden" name="target_student_id" value="<?php echo (int) $student['id']; ?>">
                    <label>
                        <span>รหัสนักศึกษาของพี่รหัส</span>
                        <input name="parent_student_code" value="<?php echo h($currentParentCode); ?>" placeholder="เว้นว่างเพื่อถอดออกจากสาย" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                    </label>
                    <button type="submit" class="rounded-md bg-teal-700 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-800">บันทึกการแก้ไข</button>
                </form>
            </details>

            <?php if ($hasParent): ?>
                <form method="post" action="student-line.php?id=<?php echo (int) $returnStudentId; ?>" class="admin-line-action-form" data-action-label="ถอดนักศึกษาออกจากสาย">
                    <input type="hidden" name="action" value="unlink">
                    <input type="hidden" name="target_student_id" value="<?php echo (int) $student['id']; ?>">
                    <button type="submit" class="admin-line-delete-button">ถอดออกจากสาย</button>
                </form>
            <?php endif; ?>
        </div>
    </article>
    <?php
}

$student = $lineage['student'];
$ancestors = $lineage['ancestors'];
$descendants = $lineage['descendants'];
$success = Session::flash('success');
$error = Session::flash('error');

ob_start();
?>
<?php if ($success): ?>
    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo h($error); ?></div>
<?php endif; ?>

<section class="mb-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-center gap-4">
            <?php echo renderStudentAvatar($student, 'h-20 w-20'); ?>
            <div>
                <p class="text-sm font-medium text-slate-500">นักศึกษาที่กำลังจัดการ</p>
                <h3 class="mt-1 text-2xl font-bold"><?php echo h($student['student_code']); ?> - <?php echo h(adminLineStudentName($student)); ?></h3>
                <p class="mt-2 text-sm text-slate-600">รุ่น <?php echo h($student['generation']); ?> · <?php echo h($student['faculty']); ?> / <?php echo h($student['major']); ?></p>
            </div>
        </div>

        <div class="grid w-full gap-3 lg:max-w-md">
            <form method="post" action="student-line.php?id=<?php echo (int) $id; ?>" class="admin-line-action-form admin-line-quick-form" data-action-label="เพิ่มหรือเปลี่ยนพี่รหัส">
                <input type="hidden" name="action" value="set_parent">
                <input type="hidden" name="target_student_id" value="<?php echo (int) $student['id']; ?>">
                <label for="admin_parent_code">เพิ่มหรือเปลี่ยนพี่รหัส</label>
                <div>
                    <input id="admin_parent_code" name="parent_student_code" value="<?php echo h(isset($student['parent_student_code']) ? $student['parent_student_code'] : ''); ?>" placeholder="รหัสนักศึกษาของพี่รหัส" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-md bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800">บันทึก</button>
                </div>
            </form>

            <form method="post" action="student-line.php?id=<?php echo (int) $id; ?>" class="admin-line-action-form admin-line-quick-form" data-action-label="เพิ่มนักศึกษาเป็นน้องรหัส">
                <input type="hidden" name="action" value="add_child">
                <label for="admin_child_code">เพิ่มน้องรหัสเข้ามาในสาย</label>
                <div>
                    <input id="admin_child_code" name="child_student_code" placeholder="รหัสนักศึกษาของน้องรหัส" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-md bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800">เพิ่ม</button>
                </div>
            </form>
        </div>
    </div>
</section>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold">พี่รหัสขึ้นไป</h3>
            <span class="text-sm text-slate-500"><?php echo h(count($ancestors)); ?> คน</span>
        </div>

        <div class="admin-line-card-scroll mt-4 space-y-3">
            <?php if (empty($ancestors)): ?>
                <p class="text-sm text-slate-500">ยังไม่มีข้อมูลพี่รหัส</p>
            <?php endif; ?>
            <?php foreach ($ancestors as $ancestor): ?>
                <?php renderAdminLineCard($ancestor, 'ชั้นที่ ' . $ancestor['line_level'], $id); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold">น้องรหัสลงไป</h3>
            <span class="text-sm text-slate-500"><?php echo h(count($descendants)); ?> คน</span>
        </div>

        <div class="admin-line-card-scroll mt-4 space-y-3">
            <?php if (empty($descendants)): ?>
                <p class="text-sm text-slate-500">ยังไม่มีข้อมูลน้องรหัส</p>
            <?php endif; ?>
            <?php foreach ($descendants as $descendant): ?>
                <?php renderAdminLineCard($descendant, 'ชั้นที่ ' . $descendant['line_level'], $id); ?>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="mt-6">
    <a href="students.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">กลับหน้ารายการ</a>
</div>
<?php
$content = ob_get_clean();

renderAdminLayout('จัดการสายรหัส', 'students', $content);
