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

        <div class="rounded-md bg-slate-50 p-4 text-sm text-slate-700">
            Header ที่รองรับ:
            <code>รหัสนักศึกษา , ชื่อจริง , นามสกุล , ชื่อเล่น , ปีการศึกษา , คณะ , สาขา , เบอร์โทร ,facebook , instagram , line id , รูป , สายรหัส</code>
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
