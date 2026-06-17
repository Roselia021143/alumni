<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$stats = $studentModel->dashboardStats();
$labels = [];
$values = [];

foreach ($stats['lines'] as $line) {
    $labels[] = $line['student']['student_code'] . ' (' . $line['percent'] . '%)';
    $values[] = $line['count'];
}

ob_start();
?>
<section class="grid gap-4 md:grid-cols-3">
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">นักศึกษาทั้งหมด</p>
        <h3 class="mt-2 text-3xl font-bold"><?php echo h($stats['total_students']); ?></h3>
    </article>
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">สายรหัสทั้งหมด</p>
        <h3 class="mt-2 text-3xl font-bold"><?php echo h($stats['total_lines']); ?></h3>
    </article>
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">Module</p>
        <h3 class="mt-2 text-3xl font-bold">Students</h3>
    </article>
</section>

<section class="mt-6 grid gap-6 lg:grid-cols-2">
    <article class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h3 class="text-lg font-semibold">สัดส่วนแต่ละสายรหัส</h3>
        <div class="mt-5 h-80">
            <canvas id="lineChart"></canvas>
        </div>
    </article>

    <article class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h3 class="text-lg font-semibold">รายละเอียดสายรหัส</h3>
        <div class="admin-line-details-scroll mt-4 space-y-3">
            <?php if (empty($stats['lines'])): ?>
                <p class="text-sm text-slate-500">ยังไม่มีข้อมูลสายรหัส</p>
            <?php endif; ?>
            <?php foreach ($stats['lines'] as $line): ?>
                <div class="rounded-md border border-slate-200 p-4">
                    <p class="font-semibold"><?php echo h($line['student']['student_code'] . ' - ' . $line['student']['first_name'] . ' ' . $line['student']['last_name']); ?></p>
                    <p class="mt-1 text-sm text-slate-600"><?php echo h($line['count']); ?> คน · <?php echo h($line['percent']); ?>%</p>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php
$content = ob_get_clean();

$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById("lineChart");
if (ctx) {
    new Chart(ctx, {
        type: "doughnut",
        data: {
            labels: ' . json_encode($labels) . ',
            datasets: [{
                data: ' . json_encode($values) . ',
                backgroundColor: ["#9f1d35", "#12345b", "#d9a441", "#0f766e", "#7c3aed", "#ea580c", "#2563eb", "#be123c"]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: "bottom" } }
        }
    });
}
</script>';

renderAdminLayout('Dashboard', 'dashboard', $content, '', $extraScripts);
