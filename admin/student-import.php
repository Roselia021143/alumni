<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$resultText = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'กรุณาเลือกไฟล์ CSV';
    } else {
        $studentModel = new Student($conn);

        try {
            $result = $studentModel->importCsv($_FILES['csv_file']['tmp_name']);
            $resultText = 'Import ใหม่ ' . $result['imported'] . ' รายการ, ข้ามรหัสซ้ำ ' . $result['skipped_duplicates'] . ' รายการ, สร้างบัญชีผู้ใช้ ' . $result['created_users'] . ' รายการ';
        } catch (Exception $exception) {
            $error = $exception->getMessage();
        }
    }
}

$supportedHeaders = [
    'student_code' => 'รหัสนักศึกษา',
    'first_name' => 'ชื่อจริง',
    'last_name' => 'นามสกุล',
    'nickname' => 'ชื่อเล่น',
    'generation' => 'ปีการศึกษา / รุ่น',
    'faculty' => 'คณะ',
    'major' => 'สาขา',
    'phone' => 'เบอร์โทร',
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'line_id_contact' => 'Line ID',
    'profile_image' => 'รูปโปรไฟล์',
    'parent_student_code' => 'สายรหัส (รหัสพี่รหัส)',
];

ob_start();
?>
<?php if ($resultText): ?>
    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        <?php echo h($resultText); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?php echo h($error); ?>
    </div>
<?php endif; ?>

<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <form method="post" enctype="multipart/form-data" class="space-y-5">
        <div>
            <label for="csv_file" class="mb-2 block text-sm font-medium">ไฟล์ CSV</label>
            <input
                id="csv_file"
                name="csv_file"
                type="file"
                accept=".csv,text/csv"
                required
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-teal-700 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white"
            >
        </div>

        <div class="rounded-lg border border-emerald-400/20 bg-slate-50 p-5">
            <div class="mb-4">
                <h3 class="text-base font-bold text-emerald-300">Header ที่รองรับในไฟล์ CSV</h3>
                <p class="mt-1 text-sm text-cyan-100/80">ใช้ชื่อคอลัมน์ภาษาอังกฤษด้านล่างเป็นแถวแรกของไฟล์</p>
            </div>

            <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($supportedHeaders as $header => $label): ?>
                    <li class="rounded-md border border-white/10 bg-white/5 px-3 py-2.5">
                        <code class="block font-bold text-yellow-300"><?php echo h($header); ?></code>
                        <span class="mt-1 block text-xs font-medium text-cyan-100"><?php echo h($label); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="mt-4 text-xs font-medium text-amber-200">คอลัมน์ที่จำเป็น: student_code, first_name และ last_name</p>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">
                Import
            </button>
            <a href="students.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">กลับ</a>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();

renderAdminLayout('Import นักศึกษา', 'import', $content);
