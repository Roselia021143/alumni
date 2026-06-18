<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$generation = isset($_GET['generation']) ? trim($_GET['generation']) : '';
$faculty = isset($_GET['faculty']) ? trim($_GET['faculty']) : '';
$major = isset($_GET['major']) ? trim($_GET['major']) : '';
$filters = [
    'generation' => $generation,
    'faculty' => $faculty,
    'major' => $major,
];
$filterOptions = $studentModel->filterOptions();
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$totalStudents = $studentModel->countAll($keyword, $filters);
$totalPages = max(1, (int) ceil($totalStudents / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $studentModel->delete((int) $_POST['delete_id']);
    Session::flash('success', 'ลบข้อมูลนักศึกษาเรียบร้อยแล้ว');
    $returnQuery = isset($_POST['return_query']) ? trim($_POST['return_query']) : '';
    header('Location: students.php' . ($returnQuery !== '' ? '?' . $returnQuery : ''));
    exit;
}

$students = $studentModel->all($keyword, $perPage, $offset, $filters);
$success = Session::flash('success');
$error = Session::flash('error');
$queryParams = array_filter([
    'keyword' => $keyword,
    'generation' => $generation,
    'faculty' => $faculty,
    'major' => $major,
], function ($value) {
    return $value !== '';
});

function pageUrl($page, $queryParams)
{
    $params = $queryParams;
    $params['page'] = $page;

    return 'students.php?' . http_build_query($params);
}

ob_start();
?>
<?php if ($success): ?>
    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        <?php echo h($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <?php echo h($error); ?>
    </div>
<?php endif; ?>

<div class="mb-5 space-y-3">
    <form method="get" class="grid gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200 md:grid-cols-2 xl:grid-cols-4">
        <label class="md:col-span-2 xl:col-span-1">
            <span class="mb-1 block text-xs font-semibold text-slate-600">ค้นหา</span>
            <input
                name="keyword"
                value="<?php echo h($keyword); ?>"
                placeholder="รหัสนักศึกษาหรือชื่อ"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
            >
        </label>

        <label>
            <span class="mb-1 block text-xs font-semibold text-slate-600">ปีการศึกษา / รุ่น</span>
            <select name="generation" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                <option value="">ทั้งหมด</option>
                <?php foreach ($filterOptions['generations'] as $option): ?>
                    <option value="<?php echo h($option); ?>" <?php echo (string) $option === $generation ? 'selected' : ''; ?>><?php echo h($option); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span class="mb-1 block text-xs font-semibold text-slate-600">คณะ</span>
            <select name="faculty" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                <option value="">ทั้งหมด</option>
                <?php foreach ($filterOptions['faculties'] as $option): ?>
                    <option value="<?php echo h($option); ?>" <?php echo $option === $faculty ? 'selected' : ''; ?>><?php echo h($option); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span class="mb-1 block text-xs font-semibold text-slate-600">สาขา</span>
            <select name="major" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                <option value="">ทั้งหมด</option>
                <?php foreach ($filterOptions['majors'] as $option): ?>
                    <option value="<?php echo h($option); ?>" <?php echo $option === $major ? 'selected' : ''; ?>><?php echo h($option); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-4">
            <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">ค้นหาและกรอง</button>
            <?php if (!empty($queryParams)): ?>
                <a href="students.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">ล้างตัวกรอง</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="flex gap-2">
        <a href="student-import.php" class="rounded-md border border-teal-700 px-4 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">Import CSV</a>
        <a href="student-form.php" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">เพิ่มนักศึกษา</a>
    </div>
</div>

<div class="mb-3 text-sm text-slate-600">
    แสดง <?php echo h($totalStudents === 0 ? 0 : $offset + 1); ?>-<?php echo h(min($offset + $perPage, $totalStudents)); ?>
    จากทั้งหมด <?php echo h($totalStudents); ?> รายการ
</div>

<section class="overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">นักศึกษา</th>
                    <th class="px-4 py-3">รุ่น</th>
                    <th class="px-4 py-3">คณะ / สาขา</th>
                    <th class="px-4 py-3">โทร</th>
                    <th class="px-4 py-3">พี่รหัส</th>
                    <th class="px-4 py-3 text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">ไม่พบข้อมูลนักศึกษา</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <?php echo renderStudentAvatar($student, 'h-11 w-11'); ?>
                                <div>
                                    <p class="font-medium"><?php echo h($student['student_code']); ?></p>
                                    <p class="text-sm text-slate-600">
                                        <?php echo h($student['first_name'] . ' ' . $student['last_name']); ?>
                                        <?php if ($student['nickname']): ?>
                                            <span>(<?php echo h($student['nickname']); ?>)</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3"><?php echo h($student['generation']); ?></td>
                        <td class="px-4 py-3"><?php echo h($student['faculty'] . ' / ' . $student['major']); ?></td>
                        <td class="whitespace-nowrap px-4 py-3"><?php echo h($student['phone']); ?></td>
                        <td class="whitespace-nowrap px-4 py-3"><?php echo h($student['parent_student_code']); ?></td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="student-line.php?id=<?php echo (int) $student['id']; ?>" class="rounded-md border border-teal-300 px-3 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-50">สายรหัส</a>
                                <a href="student-form.php?id=<?php echo (int) $student['id']; ?>" class="rounded-md border border-slate-300 px-3 py-1.5 text-xs font-medium hover:bg-slate-50">แก้ไข</a>
                                <form method="post" onsubmit="return confirm('ยืนยันการลบข้อมูลนักศึกษานี้?');">
                                    <input type="hidden" name="delete_id" value="<?php echo (int) $student['id']; ?>">
                                    <input type="hidden" name="return_query" value="<?php echo h(http_build_query($queryParams)); ?>">
                                    <button type="submit" class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($totalPages > 1): ?>
    <nav class="mt-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between" aria-label="Pagination">
        <p class="text-sm text-slate-600">หน้า <?php echo h($page); ?> จาก <?php echo h($totalPages); ?></p>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo h(pageUrl(max(1, $page - 1), $queryParams)); ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium <?php echo $page <= 1 ? 'pointer-events-none opacity-50' : 'hover:bg-slate-50'; ?>">ก่อนหน้า</a>

            <?php $startPage = max(1, $page - 2); ?>
            <?php $endPage = min($totalPages, $page + 2); ?>
            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="<?php echo h(pageUrl($i, $queryParams)); ?>" class="rounded-md border px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 hover:bg-slate-50'; ?>">
                    <?php echo h($i); ?>
                </a>
            <?php endfor; ?>

            <a href="<?php echo h(pageUrl(min($totalPages, $page + 1), $queryParams)); ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium <?php echo $page >= $totalPages ? 'pointer-events-none opacity-50' : 'hover:bg-slate-50'; ?>">ถัดไป</a>
        </div>
    </nav>
<?php endif; ?>
<?php
$content = ob_get_clean();

renderAdminLayout('จัดการนักศึกษา', 'students', $content);
