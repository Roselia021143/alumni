<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$studentId = Session::studentId();
$student = $studentModel->find($studentId);
$showAll = isset($_GET['all']);

if (!$student) {
    header('Location: profile.php');
    exit;
}

function studentTreeName($student)
{
    $name = trim((string) $student['first_name'] . ' ' . (string) $student['last_name']);
    $nickname = trim((string) $student['nickname']);

    return $nickname === '' ? $name : $name . ' (' . $nickname . ')';
}

function collectStudentTreeMembers($student, $childrenByParent, &$members, &$visited)
{
    $studentId = (int) $student['id'];

    if (isset($visited[$studentId])) {
        return;
    }

    $visited[$studentId] = true;
    $members[] = $student;
    $children = isset($childrenByParent[$studentId]) ? $childrenByParent[$studentId] : [];

    foreach ($children as $child) {
        collectStudentTreeMembers($child, $childrenByParent, $members, $visited);
    }
}

$major = trim((string) $student['major']);

if ($major === '') {
    $forest = [
        'roots' => [$student],
        'children_by_parent' => [],
        'total_students' => 1,
        'total_lines' => 1,
    ];
} else {
    $forest = $studentModel->publicProgramForest([$major]);
}

$allProgramLines = [];

foreach ($forest['roots'] as $root) {
    $members = [];
    $visited = [];
    collectStudentTreeMembers($root, $forest['children_by_parent'], $members, $visited);
    $containsCurrentStudent = false;

    foreach ($members as $member) {
        if ((int) $member['id'] === (int) $studentId) {
            $containsCurrentStudent = true;
            break;
        }
    }

    $allProgramLines[] = [
        'root' => $root,
        'members' => $members,
        'is_current' => $containsCurrentStudent,
    ];
}

$visibleLines = $showAll
    ? $allProgramLines
    : array_values(array_filter($allProgramLines, function ($line) {
        return $line['is_current'];
    }));

if (empty($visibleLines)) {
    $visibleLines[] = [
        'root' => $student,
        'members' => [$student],
        'is_current' => true,
    ];
}

$generationMap = [];
$programLines = [];
$visibleStudentCount = 0;

foreach ($visibleLines as $lineIndex => $line) {
    $membersByGeneration = [];

    foreach ($line['members'] as $member) {
        $generation = trim((string) $member['generation']);
        $generation = $generation === '' ? 'ไม่ระบุ' : $generation;
        $generationMap[$generation] = true;

        if (!isset($membersByGeneration[$generation])) {
            $membersByGeneration[$generation] = [];
        }

        $membersByGeneration[$generation][] = $member;
        $visibleStudentCount++;
    }

    $largestGenerationGroup = 1;
    foreach ($membersByGeneration as $generationMembers) {
        $largestGenerationGroup = max($largestGenerationGroup, count($generationMembers));
    }

    $programLines[] = [
        'number' => $lineIndex + 1,
        'root' => $line['root'],
        'members_by_generation' => $membersByGeneration,
        'is_current' => $line['is_current'],
        'column_width' => $largestGenerationGroup > 1
            ? max(240, ($largestGenerationGroup * 190) + (($largestGenerationGroup - 1) * 12) + 28)
            : 240,
    ];
}

$generations = array_keys($generationMap);
usort($generations, function ($left, $right) {
    if ($left === 'ไม่ระบุ') return 1;
    if ($right === 'ไม่ระบุ') return -1;
    return (int) $left <=> (int) $right;
});

$matrixColumns = ['110px'];
foreach ($programLines as $line) {
    $matrixColumns[] = (int) $line['column_width'] . 'px';
}

$treeCssVersion = filemtime(__DIR__ . '/../assets/css/student-tree-matrix.css');
$treeJsVersion = filemtime(__DIR__ . '/../assets/js/program-lines.js');

ob_start();
?>
<section class="tree-board-hero student-matrix-hero">
    <div>
        <p><?php echo $major === '' ? 'ยังไม่ได้ระบุสาขา' : h($major); ?></p>
        <h2>แผนผังสายรหัส</h2>
        <span><?php echo $showAll ? 'แสดงทุกสายในสาขาของคุณ' : 'แสดงเฉพาะต้นสายที่คุณเป็นสมาชิก'; ?></span>
    </div>
    <div class="tree-board-actions">
        <a href="tree.php" class="<?php echo $showAll ? '' : 'active'; ?>">สายของฉัน</a>
        <a href="tree.php?all=1" class="<?php echo $showAll ? 'active' : ''; ?>">ทุกสายในสาขา</a>
    </div>
</section>

<section class="student-matrix-summary" aria-label="สรุปแผนผัง">
    <article><strong><?php echo h(count($programLines)); ?></strong><span>สายรหัสที่แสดง</span></article>
    <article><strong><?php echo h($visibleStudentCount); ?></strong><span>สมาชิกที่แสดง</span></article>
    <article><strong><?php echo h(count($generations)); ?></strong><span>ปีการศึกษา</span></article>
</section>

<section class="student-lineage-map" aria-label="แผนผังสายรหัสแบบซูมและลากได้">
    <div class="map-toolbar" aria-label="เครื่องมือแผนผัง">
        <button type="button" data-zoom="out" title="ซูมออก" aria-label="ซูมออก">−</button>
        <output id="zoomLevel">100%</output>
        <button type="button" data-zoom="in" title="ซูมเข้า" aria-label="ซูมเข้า">+</button>
        <button class="fit-button" type="button" data-zoom="fit">พอดีจอ</button>
    </div>

    <div id="lineageViewport" class="lineage-viewport" tabindex="0">
        <div id="lineageStage" class="lineage-stage">
            <div id="lineageCanvas" class="lineage-canvas">
                <div class="lineage-matrix" style="grid-template-columns: <?php echo h(implode(' ', $matrixColumns)); ?>;">
                    <svg class="matrix-connectors" aria-hidden="true"></svg>
                    <div class="matrix-corner">ปีการศึกษา</div>

                    <?php foreach ($programLines as $line): ?>
                        <div class="matrix-line-head <?php echo $line['is_current'] ? 'is-current' : ''; ?>">
                            <span><?php echo $line['is_current'] ? 'สายของฉัน' : 'สายที่ ' . (int) $line['number']; ?></span>
                            <strong><?php echo h(studentTreeName($line['root'])); ?></strong>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($generations as $generation): ?>
                        <div class="matrix-generation"><small>รุ่น</small><strong><?php echo h($generation); ?></strong></div>

                        <?php foreach ($programLines as $line): ?>
                            <?php $members = isset($line['members_by_generation'][$generation]) ? $line['members_by_generation'][$generation] : []; ?>
                            <div class="matrix-cell <?php echo empty($members) ? 'is-empty' : ''; ?> <?php echo count($members) > 1 ? 'has-branches' : ''; ?>">
                                <?php if (empty($members)): ?>
                                    <span class="matrix-empty-mark">—</span>
                                <?php else: ?>
                                    <?php foreach ($members as $member): ?>
                                        <article
                                            class="lineage-node <?php echo (int) $member['id'] === (int) $studentId ? 'is-self' : ''; ?> <?php echo (int) $member['id'] === (int) $line['root']['id'] ? 'is-root' : ''; ?>"
                                            data-node-id="<?php echo (int) $member['id']; ?>"
                                            <?php if ($member['parent_student_id'] !== null): ?>data-parent-id="<?php echo (int) $member['parent_student_id']; ?>"<?php endif; ?>
                                        >
                                            <h3><?php echo h(studentTreeName($member)); ?></h3>
                                            <p><?php echo (int) $member['id'] === (int) $studentId ? 'คุณ · ' : ''; ?>ปีการศึกษา <?php echo h($generation); ?></p>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <p class="map-hint">ลากเพื่อเลื่อนดู · หมุนล้อเมาส์หรือกางสองนิ้วเพื่อซูม · ดับเบิลคลิกเพื่อพอดีจอ</p>
</section>
<?php
$content = ob_get_clean();
$extraHead = '<link rel="stylesheet" href="../assets/css/student-tree-matrix.css?v=' . h($treeCssVersion) . '">';
$extraScripts = '<script src="../assets/js/program-lines.js?v=' . h($treeJsVersion) . '"></script>';

renderStudentLayout('แผนผังสายรหัส', $content, $extraHead, $extraScripts);
