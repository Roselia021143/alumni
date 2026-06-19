<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Student.php';
require_once __DIR__ . '/classes/ViewHelper.php';

$programs = [
    'computer-science' => [
        'name' => 'วิทยาการคอมพิวเตอร์',
        'short' => 'Computer Science',
        'keywords' => ['วิทยาการคอมพิวเตอร์'],
    ],
    'information-technology' => [
        'name' => 'เทคโนโลยีสารสนเทศ',
        'short' => 'Information Technology',
        'keywords' => ['เทคโนโลยีสารสนเทศ'],
    ],
    'multimedia' => [
        'name' => 'เทคโนโลยีมัลติมีเดียและแอนิเมชัน',
        'short' => 'Multimedia & Animation',
        'keywords' => ['มัลติมีเดีย', 'มัลติมิเดีย', 'แอนิเมชัน', 'แอนิเมชั่น'],
    ],
];

$programKey = isset($_GET['program']) ? (string) $_GET['program'] : '';

if (!isset($programs[$programKey])) {
    http_response_code(404);
    $programKey = 'computer-science';
}

$program = $programs[$programKey];
$studentModel = new Student($conn);
$forest = $studentModel->publicProgramForest($program['keywords']);
$cssVersion = filemtime(__DIR__ . '/assets/css/program-lines.css');
$jsVersion = filemtime(__DIR__ . '/assets/js/program-lines.js');

function programStudentName($student)
{
    $name = trim((string) $student['first_name'] . ' ' . (string) $student['last_name']);
    $nickname = trim((string) $student['nickname']);

    return $nickname === '' ? $name : $name . ' (' . $nickname . ')';
}

function renderProgramBranch($student, $childrenByParent, $visited = [])
{
    $studentId = (int) $student['id'];

    if (isset($visited[$studentId])) {
        return;
    }

    $visited[$studentId] = true;
    $children = isset($childrenByParent[$studentId]) ? $childrenByParent[$studentId] : [];
    ?>
    <li>
        <article class="lineage-node">
            <h3><?php echo h(programStudentName($student)); ?></h3>
            <p>ปีการศึกษา <?php echo h($student['generation']); ?></p>
        </article>
        <?php if (!empty($children)): ?>
            <ul>
                <?php foreach ($children as $child): ?>
                    <?php renderProgramBranch($child, $childrenByParent, $visited); ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </li>
    <?php
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="แผนผังสายรหัสหลักสูตร<?php echo h($program['name']); ?>">
    <title>สายรหัส<?php echo h($program['name']); ?> | <?php echo h(APP_NAME); ?></title>
    <link rel="icon" type="image/png" href="assets/images/scitec-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/program-lines.css?v=<?php echo h($cssVersion); ?>">
</head>
<body>
    <header class="lineage-header">
        <a class="lineage-brand" href="index.php#programs">
            <img src="assets/images/scitec-logo.png" alt="">
            <span><strong>CIT Code Line</strong><small>Alumni System</small></span>
        </a>
        <a class="back-link" href="index.php#programs">← กลับหน้าหลัก</a>
    </header>

    <main>
        <section class="lineage-intro">
            <div>
                <p><?php echo h($program['short']); ?></p>
                <h1>สายรหัสหลักสูตร<?php echo h($program['name']); ?></h1>
                <span>ลากพื้นที่เพื่อเลื่อนดู · หมุนล้อเมาส์หรือกางสองนิ้วเพื่อซูม</span>
            </div>
            <div class="lineage-summary" aria-label="สรุปข้อมูล">
                <article><strong><?php echo h($forest['total_lines']); ?></strong><span>สายรหัส</span></article>
                <article><strong><?php echo h($forest['total_students']); ?></strong><span>สมาชิก</span></article>
            </div>
        </section>

        <section class="lineage-map" aria-label="แผนผังสายรหัสแบบซูมและลากได้">
            <div class="map-toolbar" aria-label="เครื่องมือแผนผัง">
                <button type="button" data-zoom="out" title="ซูมออก" aria-label="ซูมออก">−</button>
                <output id="zoomLevel">100%</output>
                <button type="button" data-zoom="in" title="ซูมเข้า" aria-label="ซูมเข้า">+</button>
                <button class="fit-button" type="button" data-zoom="fit">พอดีจอ</button>
            </div>

            <div id="lineageViewport" class="lineage-viewport" tabindex="0">
                <div id="lineageStage" class="lineage-stage">
                    <div id="lineageCanvas" class="lineage-canvas">
                        <?php if (empty($forest['roots'])): ?>
                            <div class="lineage-empty">
                                <strong>ยังไม่มีข้อมูลสายรหัส</strong>
                                <span>เมื่อมีการเพิ่มข้อมูลในหลักสูตร แผนผังจะแสดงที่นี่โดยอัตโนมัติ</span>
                            </div>
                        <?php else: ?>
                            <ul class="lineage-forest">
                                <?php foreach ($forest['roots'] as $root): ?>
                                    <?php renderProgramBranch($root, $forest['children_by_parent']); ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <p class="map-hint">เคล็ดลับ: ดับเบิลคลิกบนพื้นที่ว่างเพื่อกลับมาดูแบบพอดีจอ</p>
        </section>
    </main>

    <script src="assets/js/program-lines.js?v=<?php echo h($jsVersion); ?>"></script>
</body>
</html>
