<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/SystemSetting.php';

$studentModel = new Student($conn);
$settingModel = new SystemSetting($conn);
$stats = $studentModel->academicMajorStats($settingModel->currentAcademicYear());
$majors = array_values($stats['majors']);

$majorLabels = array_column($majors, 'label');
$majorTotals = array_map(function ($major) {
    return $major['total'];
}, $majors);
$majorColors = ['#42f59b', '#ffd857', '#60a5fa'];
$yearColors = ['#42f59b', '#60a5fa', '#ffd857', '#c084fc', '#64748b'];

ob_start();
?>
<section class="grid gap-4 md:grid-cols-3">
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">นักศึกษา 3 สาขา</p>
        <h3 class="mt-2 text-3xl font-bold"><?php echo h(number_format($stats['total_students'])); ?></h3>
        <p class="mt-1 text-xs text-slate-500">คนทั้งหมดในฐานข้อมูล</p>
    </article>
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">สายรหัสทั้งหมด</p>
        <h3 class="mt-2 text-3xl font-bold"><?php echo h(number_format($stats['total_lines'])); ?></h3>
        <p class="mt-1 text-xs text-slate-500">รวมทั้ง 3 สาขา</p>
    </article>
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">ปีการศึกษาที่ใช้คำนวณ</p>
        <h3 class="mt-2 text-3xl font-bold"><?php echo h($stats['current_academic_year']); ?></h3>
        <p class="mt-1 text-xs text-slate-500">สำหรับจำแนกชั้นปี 1–4</p>
    </article>
</section>

<section class="mt-6 grid gap-6 lg:grid-cols-5">
    <article class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-3">
        <div>
            <p class="text-sm font-semibold text-teal-700">ภาพรวม 3 สาขา</p>
            <h3 class="mt-1 text-lg font-semibold">จำนวนนักศึกษาแยกตามสาขา</h3>
            <p class="mt-1 text-sm text-slate-500">เปรียบเทียบจำนวนรวมของนักศึกษาในแต่ละสาขา</p>
        </div>
        <div class="mt-5 h-80">
            <canvas id="majorBarChart"></canvas>
        </div>
    </article>

    <article class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
        <div>
            <p class="text-sm font-semibold text-teal-700">สัดส่วนรวม</p>
            <h3 class="mt-1 text-lg font-semibold">นักศึกษาแต่ละสาขา</h3>
            <p class="mt-1 text-sm text-slate-500">คิดเป็นสัดส่วนจากนักศึกษาทั้ง 3 สาขา</p>
        </div>
        <div class="mt-5 h-80">
            <canvas id="majorDoughnutChart"></canvas>
        </div>
    </article>
</section>

<section class="mt-6">
    <div class="mb-4">
        <p class="text-sm font-semibold text-teal-700">รายละเอียดชั้นปี</p>
        <h3 class="mt-1 text-xl font-semibold">สัดส่วนปี 1–4 ของแต่ละสาขา</h3>
        <p class="mt-1 text-sm text-slate-500">เปอร์เซ็นต์คำนวณเทียบกับนักศึกษาทั้งหมดภายในสาขานั้น</p>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <?php foreach ($majors as $index => $major): ?>
            <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-teal-700">สาขาที่ <?php echo (int) $index + 1; ?></p>
                        <h4 class="mt-1 font-semibold"><?php echo h($major['label']); ?></h4>
                    </div>
                    <div class="rounded-md bg-slate-50 px-3 py-2 text-right">
                        <strong class="block text-xl"><?php echo h(number_format($major['total'])); ?></strong>
                        <span class="text-xs text-slate-500">คน</span>
                    </div>
                </div>

                <div class="mx-auto mt-4 h-56 max-w-xs">
                    <canvas id="yearChart<?php echo (int) $index; ?>"></canvas>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <?php foreach ($major['years'] as $year => $count): ?>
                        <div class="rounded-md border border-slate-200 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs text-slate-500">ปี <?php echo (int) $year; ?></span>
                                <strong class="text-sm"><?php echo h(number_format($count)); ?> คน</strong>
                            </div>
                            <p class="mt-1 text-xs font-semibold text-teal-700"><?php echo h(number_format($major['percentages'][$year], 1)); ?>%</p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($major['other_years'] > 0): ?>
                    <p class="mt-3 text-xs text-slate-500">ศิษย์เก่าหรือรุ่นอื่น <?php echo h(number_format($major['other_years'])); ?> คน</p>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();

$yearChartData = [];
foreach ($majors as $major) {
    $yearChartData[] = [
        'label' => $major['label'],
        'values' => array_merge(array_values($major['years']), [$major['other_years']]),
    ];
}

$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dashboardTextColor = "#b7c6d8";
const dashboardGridColor = "rgba(183, 198, 216, 0.12)";
const majorLabels = ' . json_encode($majorLabels, JSON_UNESCAPED_UNICODE) . ';
const majorTotals = ' . json_encode($majorTotals) . ';
const majorColors = ' . json_encode($majorColors) . ';
const yearColors = ' . json_encode($yearColors) . ';
const yearChartData = ' . json_encode($yearChartData, JSON_UNESCAPED_UNICODE) . ';

Chart.defaults.color = dashboardTextColor;
Chart.defaults.font.family = "Prompt, Tahoma, sans-serif";

new Chart(document.getElementById("majorBarChart"), {
    type: "bar",
    data: {
        labels: majorLabels,
        datasets: [{
            label: "จำนวนนักศึกษา",
            data: majorTotals,
            backgroundColor: majorColors.map(color => color + "B8"),
            borderColor: majorColors,
            borderWidth: 1,
            borderRadius: 8,
            maxBarThickness: 72
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: dashboardGridColor } },
            x: { grid: { display: false } }
        },
        plugins: { legend: { display: false } }
    }
});

new Chart(document.getElementById("majorDoughnutChart"), {
    type: "doughnut",
    data: {
        labels: majorLabels,
        datasets: [{ data: majorTotals, backgroundColor: majorColors, borderWidth: 0, spacing: 3 }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "64%",
        plugins: {
            legend: { position: "bottom", labels: { usePointStyle: true, padding: 16 } },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                        const percent = total > 0 ? ((context.raw / total) * 100).toFixed(1) : "0.0";
                        return " " + context.raw + " คน (" + percent + "%)";
                    }
                }
            }
        }
    }
});

yearChartData.forEach(function(major, index) {
    new Chart(document.getElementById("yearChart" + index), {
        type: "doughnut",
        data: {
            labels: ["ปี 1", "ปี 2", "ปี 3", "ปี 4", "ศิษย์เก่า/รุ่นอื่น"],
            datasets: [{ data: major.values, backgroundColor: yearColors, borderWidth: 0, spacing: 2 }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "58%",
            plugins: {
                legend: { position: "bottom", labels: { usePointStyle: true, boxWidth: 8, padding: 10, font: { size: 10 } } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                            const percent = total > 0 ? ((context.raw / total) * 100).toFixed(1) : "0.0";
                            return " " + context.raw + " คน (" + percent + "%)";
                        }
                    }
                }
            }
        }
    });
});
</script>';

renderAdminLayout('Dashboard', 'dashboard', $content, '', $extraScripts);
