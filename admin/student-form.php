<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$student = $id > 0 ? $studentModel->find($id) : null;

if ($id > 0 && !$student) {
    Session::flash('error', 'ไม่พบข้อมูลนักศึกษา');
    header('Location: students.php');
    exit;
}

$defaults = [
    'student_code' => '',
    'first_name' => '',
    'last_name' => '',
    'nickname' => '',
    'generation' => '',
    'faculty' => '',
    'major' => '',
    'phone' => '',
    'facebook' => '',
    'instagram' => '',
    'line_id_contact' => '',
    'parent_student_id' => '',
];
$student = $student ? array_merge($defaults, $student) : $defaults;
$title = $id ? 'แก้ไขนักศึกษา' : 'เพิ่มนักศึกษา';

ob_start();
?>
<form method="post" action="student-save.php" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

    <div class="grid gap-5 md:grid-cols-2">
        <?php
        $fields = [
            ['student_code', 'รหัสนักศึกษา', 'text', true],
            ['first_name', 'ชื่อ', 'text', true],
            ['last_name', 'นามสกุล', 'text', true],
            ['nickname', 'ชื่อเล่น', 'text', false],
            ['generation', 'รุ่น', 'number', true],
            ['faculty', 'คณะ', 'text', false],
            ['major', 'สาขา', 'text', false],
            ['phone', 'เบอร์โทร', 'text', false],
            ['facebook', 'Facebook', 'text', false],
            ['instagram', 'Instagram', 'text', false],
            ['line_id_contact', 'Line ID', 'text', false],
            ['parent_student_id', 'รหัสนักศึกษาของพี่รหัส', 'text', false],
        ];
        ?>

        <?php foreach ($fields as $field): ?>
            <div>
                <label for="<?php echo h($field[0]); ?>" class="mb-2 block text-sm font-medium"><?php echo h($field[1]); ?></label>
                <input
                    id="<?php echo h($field[0]); ?>"
                    name="<?php echo h($field[0]); ?>"
                    type="<?php echo h($field[2]); ?>"
                    value="<?php echo h($student[$field[0]]); ?>"
                    <?php echo $field[3] ? 'required' : ''; ?>
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
                >
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-6 flex justify-end gap-2">
        <a href="students.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">ยกเลิก</a>
        <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">บันทึก</button>
    </div>
</form>
<?php
$content = ob_get_clean();

renderAdminLayout($title, 'students', $content);
