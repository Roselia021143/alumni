<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

$studentModel = new Student($conn);
$studentId = Session::studentId();
$showAll = isset($_GET['all']) && $_GET['all'] === '1';
$tree = $studentModel->getTree($studentId, $showAll ? null : 2, $showAll ? null : 2);

if (!$tree) {
    header('Location: profile.php');
    exit;
}

function treeStudentName($student)
{
    $name = trim((string) $student['first_name'] . ' ' . (string) $student['last_name']);

    if (!empty($student['nickname'])) {
        $name .= ' (' . $student['nickname'] . ')';
    }

    return $name;
}

function flattenTreeDescendants($parentId, $childrenByParent, &$items = [])
{
    if (!isset($childrenByParent[$parentId])) {
        return $items;
    }

    foreach ($childrenByParent[$parentId] as $child) {
        $items[] = $child;
        flattenTreeDescendants((int) $child['id'], $childrenByParent, $items);
    }

    return $items;
}

function groupStudentsByLineLevel($students)
{
    $groups = [];

    foreach ($students as $student) {
        $level = isset($student['line_level']) ? (int) $student['line_level'] : 0;

        if (!isset($groups[$level])) {
            $groups[$level] = [];
        }

        $groups[$level][] = $student;
    }

    ksort($groups);
    return $groups;
}

function protectTreeStudentPhoto($student, $isSelf = false)
{
    if (!$isSelf && isset($student['profile_image_visible']) && (int) $student['profile_image_visible'] === 0) {
        $student['profile_image'] = '';
    }

    return $student;
}

function treeVisibleValue($student, $field, $label, $alwaysVisible = false)
{
    $value = isset($student[$field]) ? trim((string) $student[$field]) : '';
    $visibleField = $field . '_visible';

    if (!$alwaysVisible && (!array_key_exists($visibleField, $student) || (int) $student[$visibleField] === 0)) {
        $value = 'ไม่เปิดเผย';
    } elseif ($value === '') {
        $value = 'ยังไม่ระบุ';
    }

    return [
        'label' => $label,
        'value' => $value,
    ];
}

function renderTreeCard($student, $label, $variant = '')
{
    $isSelf = $variant === 'self';
    $student = protectTreeStudentPhoto($student, $isSelf);
    $className = trim('tree-board-card ' . ($isSelf ? 'tree-board-card-self' : ''));
    $details = [
        treeVisibleValue($student, 'student_code', 'รหัสนักศึกษา'),
        treeVisibleValue($student, 'generation', 'ปีการศึกษา'),
        treeVisibleValue($student, 'faculty', 'คณะ', true),
        treeVisibleValue($student, 'major', 'สาขา', true),
        treeVisibleValue($student, 'phone', 'เบอร์โทร'),
        treeVisibleValue($student, 'email', 'Email'),
        treeVisibleValue($student, 'facebook', 'Facebook'),
        treeVisibleValue($student, 'instagram', 'Instagram'),
        treeVisibleValue($student, 'line_id_contact', 'Line ID'),
    ];
    ?>
    <article class="<?php echo h($className); ?>" tabindex="0" aria-label="<?php echo h($label . ' ' . treeStudentName($student)); ?>">
        <div class="tree-board-card-inner">
            <div class="tree-board-card-face tree-board-card-front">
                <div class="tree-board-photo">
                    <?php echo renderStudentAvatar($student, $isSelf ? 'h-20 w-20' : 'h-14 w-14'); ?>
                </div>
                <div class="tree-board-card-content">
                    <span class="tree-board-badge"><?php echo h($label); ?></span>
                    <h3><?php echo h(treeStudentName($student)); ?></h3>
                    <p>ปีการศึกษา <?php echo h($student['generation']); ?></p>
                </div>
            </div>
            <div class="tree-board-card-face tree-board-card-back">
                <div class="tree-board-card-content">
                    <span class="tree-board-badge">ข้อมูลที่เปิดเผย</span>
                    <h3><?php echo h(treeStudentName($student)); ?></h3>
                    <dl class="tree-board-detail-list">
                        <?php foreach ($details as $detail): ?>
                            <div>
                                <dt><?php echo h($detail['label']); ?></dt>
                                <dd><?php echo h($detail['value']); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        </div>
    </article>
    <?php
}

$ancestors = $tree['ancestors'];
$descendants = flattenTreeDescendants((int) $tree['root']['id'], $tree['children_by_parent']);
$descendantGroups = groupStudentsByLineLevel($descendants);
$viewText = $showAll ? 'แสดงสายรหัสทั้งหมด' : 'แสดงรุ่นพี่ 2 รุ่น ตัวคุณ และรุ่นน้อง 2 รุ่น';

ob_start();
?>
<section class="tree-board-hero">
    <div>
        <p>Alumni Lineage Board</p>
        <h2>แผนผังสายรหัส</h2>
        <span><?php echo h($viewText); ?></span>
    </div>
    <div class="tree-board-actions">
        <a href="tree.php" class="<?php echo $showAll ? '' : 'active'; ?>">มุมมองย่อ</a>
        <a href="tree.php?all=1" class="<?php echo $showAll ? 'active' : ''; ?>">ดูทั้งหมด</a>
    </div>
</section>

<section class="tree-board-stats" aria-label="สรุปสายรหัส">
    <article>
        <strong><?php echo h(count($ancestors)); ?></strong>
        <span>รุ่นพี่ที่แสดง</span>
    </article>
    <article class="is-current">
        <strong>1</strong>
        <span>ตัวคุณ</span>
    </article>
    <article>
        <strong><?php echo h(count($descendants)); ?></strong>
        <span>รุ่นน้องที่แสดง</span>
    </article>
</section>

<section class="tree-board-shell">
    <div class="tree-board-section">
        <div class="tree-board-section-head">
            <span>ขึ้นไป</span>
            <h3>รุ่นพี่รหัส</h3>
        </div>

        <?php if (empty($ancestors)): ?>
            <p class="tree-board-empty">ยังไม่มีข้อมูลพี่รหัสในระบบ</p>
        <?php else: ?>
            <div class="tree-board-stack tree-board-stack-ancestors">
                <?php foreach ($ancestors as $ancestor): ?>
                    <?php renderTreeCard($ancestor, 'พี่รหัสชั้นที่ ' . (int) $ancestor['line_level']); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="tree-board-current">
        <div class="tree-board-current-line"></div>
        <?php renderTreeCard($tree['root'], 'ตัวคุณ', 'self'); ?>
        <div class="tree-board-current-line"></div>
    </div>

    <div class="tree-board-section">
        <div class="tree-board-section-head">
            <span>ลงไป</span>
            <h3>รุ่นน้องรหัส</h3>
        </div>

        <?php if (empty($descendantGroups)): ?>
            <p class="tree-board-empty">ยังไม่มีข้อมูลน้องรหัสในระบบ</p>
        <?php else: ?>
            <div class="tree-board-generations">
                <?php foreach ($descendantGroups as $level => $students): ?>
                    <div class="tree-board-generation">
                        <div class="tree-board-generation-head">
                            <span>ชั้นที่ <?php echo h($level); ?></span>
                            <strong><?php echo h(count($students)); ?> คน</strong>
                        </div>
                        <div class="tree-board-grid">
                            <?php foreach ($students as $student): ?>
                                <?php renderTreeCard($student, 'น้องรหัสชั้นที่ ' . (int) $student['line_level']); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();

renderStudentLayout('แผนผังสายรหัส', $content);
