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
            <?php if ($hasParent): ?>
                <form method="post" action="student-line.php?id=<?php echo (int) $returnStudentId; ?>" class="admin-line-action-form admin-line-unlink-form" data-student-label="<?php echo h($student['student_code'] . ' - ' . adminLineStudentName($student)); ?>">
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
            <form method="post" action="student-line.php?id=<?php echo (int) $id; ?>" class="admin-line-action-form admin-line-link-form admin-line-quick-form" data-relation="พี่รหัส">
                <input type="hidden" name="action" value="set_parent">
                <input type="hidden" name="target_student_id" value="<?php echo (int) $student['id']; ?>">
                <label for="admin_parent_code">เพิ่มหรือเปลี่ยนพี่รหัส</label>
                <div>
                    <input id="admin_parent_code" name="parent_student_code" value="<?php echo h(isset($student['parent_student_code']) ? $student['parent_student_code'] : ''); ?>" placeholder="รหัสนักศึกษาของพี่รหัส" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-md bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800">บันทึก</button>
                </div>
            </form>

            <form method="post" action="student-line.php?id=<?php echo (int) $id; ?>" class="admin-line-action-form admin-line-link-form admin-line-quick-form" data-relation="น้องรหัส">
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

<div id="adminLinkModal" class="line-confirm-modal" hidden>
    <div class="line-confirm-backdrop" data-link-modal-close></div>
    <section class="line-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="adminLinkTitle">
        <div class="line-confirm-icon" aria-hidden="true">?</div>
        <p class="text-sm font-semibold text-teal-300">ตรวจสอบข้อมูลก่อนบันทึก</p>
        <h3 id="adminLinkTitle" class="mt-2 text-xl font-bold">ยืนยันการเพิ่มสายรหัส?</h3>
        <p id="adminLinkRelation" class="mt-2 text-sm text-slate-300"></p>

        <dl class="line-confirm-student mt-5">
            <div>
                <dt>รหัสนักศึกษา</dt>
                <dd id="adminLinkCode">-</dd>
            </div>
            <div>
                <dt>ชื่อ-นามสกุล</dt>
                <dd id="adminLinkName">-</dd>
            </div>
            <div>
                <dt>รุ่น</dt>
                <dd id="adminLinkGeneration">-</dd>
            </div>
        </dl>

        <p id="adminLinkError" class="line-confirm-error" hidden></p>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="rounded-md border border-slate-500 px-4 py-2 text-sm font-medium hover:bg-white/5" data-link-modal-close>ยกเลิก</button>
            <button id="adminLinkConfirm" type="button" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800" hidden>ยืนยันและบันทึก</button>
        </div>
    </section>
</div>

<div id="adminUnlinkModal" class="line-confirm-modal" hidden>
    <div class="line-confirm-backdrop" data-unlink-modal-close></div>
    <section class="line-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="adminUnlinkTitle">
        <div class="line-confirm-icon" aria-hidden="true">!</div>
        <p class="text-sm font-semibold text-red-300">ตรวจสอบก่อนถอดสายรหัส</p>
        <h3 id="adminUnlinkTitle" class="mt-2 text-xl font-bold">ยืนยันการถอดออกจากสายรหัส?</h3>
        <p class="mt-3 text-sm text-slate-300">นักศึกษารายนี้จะถูกยกเลิกความสัมพันธ์กับพี่รหัสเดิม</p>
        <p id="adminUnlinkStudent" class="mt-5 rounded-lg border border-white/10 bg-white/5 px-4 py-3 font-semibold"></p>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="rounded-md border border-slate-500 px-4 py-2 text-sm font-medium hover:bg-white/5" data-unlink-modal-close>ยกเลิก</button>
            <button id="adminUnlinkConfirm" type="button" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">ยืนยันการถอดสาย</button>
        </div>
    </section>
</div>

<script>
(function () {
    var modal = document.getElementById('adminLinkModal');
    var confirmButton = document.getElementById('adminLinkConfirm');
    var error = document.getElementById('adminLinkError');
    var pendingForm = null;

    if (!modal || !confirmButton || !error) {
        return;
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        pendingForm = null;
    }

    document.querySelectorAll('.admin-line-link-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var action = form.querySelector('input[name="action"]');
            var codeInput = action && action.value === 'add_child'
                ? form.querySelector('input[name="child_student_code"]')
                : form.querySelector('input[name="parent_student_code"]');
            var code = codeInput ? codeInput.value.trim() : '';

            if (!code) {
                return;
            }

            event.preventDefault();
            pendingForm = form;
            confirmButton.hidden = true;
            confirmButton.disabled = false;
            confirmButton.textContent = 'ยืนยันและบันทึก';
            error.hidden = true;
            document.getElementById('adminLinkRelation').textContent = 'กำลังเพิ่มบุคคลนี้เป็น' + (form.getAttribute('data-relation') || 'สายรหัส');
            document.getElementById('adminLinkCode').textContent = code;
            document.getElementById('adminLinkName').textContent = 'กำลังค้นหาข้อมูล...';
            document.getElementById('adminLinkGeneration').textContent = '-';
            modal.hidden = false;
            document.body.classList.add('modal-open');

            fetch('student-lookup.php?code=' + encodeURIComponent(code), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error();
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        throw new Error(data.message || 'ไม่พบข้อมูลนักศึกษา');
                    }

                    document.getElementById('adminLinkCode').textContent = data.student.student_code;
                    document.getElementById('adminLinkName').textContent = data.student.name;
                    document.getElementById('adminLinkGeneration').textContent = data.student.generation || 'ยังไม่ระบุ';
                    confirmButton.hidden = false;
                    confirmButton.focus();
                })
                .catch(function (requestError) {
                    document.getElementById('adminLinkName').textContent = '-';
                    error.textContent = requestError.message || 'ไม่สามารถตรวจสอบข้อมูลได้ กรุณาลองใหม่';
                    error.hidden = false;
                });
        });
    });

    confirmButton.addEventListener('click', function () {
        if (!pendingForm) {
            return;
        }

        confirmButton.disabled = true;
        confirmButton.textContent = 'กำลังบันทึก...';
        pendingForm.submit();
    });

    modal.querySelectorAll('[data-link-modal-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();

(function () {
    var modal = document.getElementById('adminUnlinkModal');
    var confirmButton = document.getElementById('adminUnlinkConfirm');
    var studentLabel = document.getElementById('adminUnlinkStudent');
    var pendingForm = null;

    if (!modal || !confirmButton || !studentLabel) {
        return;
    }

    function closeModal() {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        pendingForm = null;
    }

    document.querySelectorAll('.admin-line-unlink-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            pendingForm = form;
            studentLabel.textContent = form.getAttribute('data-student-label') || '-';
            modal.hidden = false;
            document.body.classList.add('modal-open');
            confirmButton.focus();
        });
    });

    confirmButton.addEventListener('click', function () {
        if (!pendingForm) {
            return;
        }

        confirmButton.disabled = true;
        confirmButton.textContent = 'กำลังถอดสาย...';
        pendingForm.submit();
    });

    modal.querySelectorAll('[data-unlink-modal-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
})();
</script>
<?php
$content = ob_get_clean();

renderAdminLayout('จัดการสายรหัส', 'students', $content);
