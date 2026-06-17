<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$studentId = Session::studentId();
$direction = isset($_GET['direction']) && $_GET['direction'] === 'down' ? 'down' : 'up';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$lineagePage = $studentModel->getLineagePage($studentId, $direction, $perPage, $offset);

if (!$lineagePage) {
    header('Location: dashboard.php');
    exit;
}

$items = $lineagePage['items'];
$total = $lineagePage['total'];
$totalPages = max(1, (int) ceil($total / $perPage));

if ($page > $totalPages) {
    header('Location: line.php?direction=' . $direction . '&page=' . $totalPages);
    exit;
}

function studentDisplayName($student)
{
    $name = $student['first_name'] . ' ' . $student['last_name'];

    if (!empty($student['nickname'])) {
        $name .= ' (' . $student['nickname'] . ')';
    }

    return $name;
}

function lineUrl($direction, $page)
{
    return 'line.php?direction=' . urlencode($direction) . '&page=' . (int) $page;
}

$title = $direction === 'up' ? 'พี่รหัสขึ้นไป' : 'น้องรหัสลงไป';

ob_start();
?>
<section class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex gap-2">
        <a href="line.php?direction=up" class="rounded-md border px-4 py-2 text-sm font-semibold <?php echo $direction === 'up' ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 hover:bg-slate-50'; ?>">รุ่นพี่ขึ้นไป</a>
        <a href="line.php?direction=down" class="rounded-md border px-4 py-2 text-sm font-semibold <?php echo $direction === 'down' ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 hover:bg-slate-50'; ?>">รุ่นน้องลงไป</a>
    </div>
    <a href="dashboard.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">กลับหน้าแรก</a>
</section>

<section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-lg font-semibold"><?php echo h($title); ?></h2>
    <p class="mt-1 text-sm text-slate-600">แสดงเฉพาะชื่อจริง นามสกุล ชื่อเล่น รูปภาพ และปีการศึกษา</p>

    <div class="mt-5 space-y-3">
        <?php if (empty($items)): ?>
            <p class="text-sm text-slate-500">ไม่พบข้อมูลในสายรหัส</p>
        <?php endif; ?>

        <?php foreach ($items as $item): ?>
            <?php
            if (isset($item['profile_image_visible']) && (int) $item['profile_image_visible'] === 0) {
                $item['profile_image'] = '';
            }
            ?>
            <a href="profile.php?id=<?php echo (int) $item['id']; ?>" class="block rounded-md border border-slate-200 p-4 hover:bg-slate-50">
                <div class="flex items-center gap-3">
                    <?php echo renderStudentAvatar($item, 'h-11 w-11'); ?>
                    <div>
                        <p class="text-xs font-semibold text-teal-700">ชั้นที่ <?php echo h($item['line_level']); ?></p>
                        <h3 class="mt-1 font-semibold"><?php echo h(studentDisplayName($item)); ?></h3>
                        <p class="mt-1 text-sm text-slate-600">ปีการศึกษา <?php echo h($item['generation']); ?></p>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($totalPages > 1): ?>
    <nav class="mt-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between" aria-label="Pagination">
        <p class="text-sm text-slate-600">หน้า <?php echo h($page); ?> จาก <?php echo h($totalPages); ?></p>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo h(lineUrl($direction, max(1, $page - 1))); ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium <?php echo $page <= 1 ? 'pointer-events-none opacity-50' : 'hover:bg-slate-50'; ?>">ก่อนหน้า</a>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="<?php echo h(lineUrl($direction, $i)); ?>" class="rounded-md border px-3 py-2 text-sm font-medium <?php echo $i === $page ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 hover:bg-slate-50'; ?>"><?php echo h($i); ?></a>
            <?php endfor; ?>
            <a href="<?php echo h(lineUrl($direction, min($totalPages, $page + 1))); ?>" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium <?php echo $page >= $totalPages ? 'pointer-events-none opacity-50' : 'hover:bg-slate-50'; ?>">ถัดไป</a>
        </div>
    </nav>
<?php endif; ?>
<?php
$content = ob_get_clean();

renderStudentLayout($title, $content);
