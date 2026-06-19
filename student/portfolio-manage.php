<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/StudentProfile.php';

$studentId = Session::studentId();
$profileModel = new StudentProfile($conn);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

        if ($action === 'add_project') {
            $profileModel->saveProject($studentId, $_POST);
            Session::flash('success', 'เพิ่มผลงานเรียบร้อยแล้ว');
        } elseif ($action === 'add_experience') {
            $profileModel->saveExperience($studentId, $_POST);
            Session::flash('success', 'เพิ่มประสบการณ์เรียบร้อยแล้ว');
        } elseif ($action === 'add_activity') {
            $profileModel->saveActivity($studentId, $_POST);
            Session::flash('success', 'เพิ่มกิจกรรมหรือความสำเร็จเรียบร้อยแล้ว');
        } elseif ($action === 'delete') {
            $profileModel->deleteItem($studentId, isset($_POST['type']) ? $_POST['type'] : '', isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0);
            Session::flash('success', 'ลบรายการเรียบร้อยแล้ว');
        }

        header('Location: portfolio-manage.php');
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$success = Session::flash('success');
$portfolio = $profileModel->getPortfolio($studentId);

function portfolioDateLabel($date)
{
    if (!$date) {
        return '';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y', $timestamp) : $date;
}

function renderDeleteButton($type, $itemId)
{
    ?>
    <form method="post" onsubmit="return confirm('ต้องการลบรายการนี้ใช่หรือไม่?');">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="type" value="<?php echo h($type); ?>">
        <input type="hidden" name="item_id" value="<?php echo (int) $itemId; ?>">
        <button type="submit" class="text-sm font-semibold text-red-400 hover:text-red-300">ลบ</button>
    </form>
    <?php
}

ob_start();
?>
<div class="profile-manage-head">
    <div>
        <p>Portfolio Manager</p>
        <h2>จัดการผลงานและประสบการณ์</h2>
        <span>เพิ่มข้อมูลที่ช่วยเล่าเรื่องราวและความสามารถของคุณ</span>
    </div>
    <a href="profile.php" class="profile-btn profile-btn-primary">ดูหน้าโปรไฟล์</a>
</div>

<?php if ($success): ?>
    <div class="mb-5 rounded-md border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300"><?php echo h($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-5 rounded-md border border-red-400/30 bg-red-400/10 px-4 py-3 text-sm text-red-300"><?php echo h($error); ?></div>
<?php endif; ?>

<div class="profile-manage-grid">
    <section class="profile-panel">
        <div class="profile-section-title"><span>01</span><h3>ผลงาน / โปรเจกต์</h3></div>
        <form method="post" class="profile-form-grid">
            <input type="hidden" name="action" value="add_project">
            <label class="profile-field profile-field-full"><span>ชื่อผลงาน *</span><input name="title" required maxlength="180" placeholder="ชื่อโปรเจกต์หรือผลงาน"></label>
            <label class="profile-field profile-field-full"><span>รายละเอียด</span><textarea name="description" rows="3" placeholder="อธิบายโจทย์ บทบาท และผลลัพธ์"></textarea></label>
            <label class="profile-field profile-field-full"><span>เทคโนโลยีที่ใช้</span><input name="technologies" placeholder="React, PHP, MySQL"></label>
            <label class="profile-field"><span>ลิงก์ผลงาน</span><input name="project_url" type="url" placeholder="https://"></label>
            <label class="profile-field"><span>Repository</span><input name="repository_url" type="url" placeholder="https://github.com/"></label>
            <label class="profile-check profile-field-full"><input type="checkbox" name="is_featured" value="1"><span>แสดงเป็นผลงานเด่น</span></label>
            <button class="profile-btn profile-btn-primary profile-field-full" type="submit">เพิ่มผลงาน</button>
        </form>
        <div class="profile-manage-list">
            <?php foreach ($portfolio['projects'] as $item): ?>
                <article><div><strong><?php echo h($item['title']); ?></strong><p><?php echo h($item['technologies']); ?></p></div><?php renderDeleteButton('project', $item['id']); ?></article>
            <?php endforeach; ?>
            <?php if (!$portfolio['projects']): ?><p class="profile-empty">ยังไม่มีผลงาน</p><?php endif; ?>
        </div>
    </section>

    <section class="profile-panel">
        <div class="profile-section-title"><span>02</span><h3>ประสบการณ์</h3></div>
        <form method="post" class="profile-form-grid">
            <input type="hidden" name="action" value="add_experience">
            <label class="profile-field"><span>ตำแหน่ง *</span><input name="position" required placeholder="Frontend Developer"></label>
            <label class="profile-field"><span>องค์กร *</span><input name="organization" required placeholder="ชื่อบริษัทหรือองค์กร"></label>
            <label class="profile-field"><span>ประเภทงาน</span><input name="employment_type" placeholder="ฝึกงาน / Full-time"></label>
            <label class="profile-field"><span>สถานที่</span><input name="location" placeholder="กรุงเทพฯ / Remote"></label>
            <label class="profile-field"><span>วันที่เริ่ม</span><input name="started_at" type="date"></label>
            <label class="profile-field"><span>วันที่สิ้นสุด</span><input name="ended_at" type="date"></label>
            <label class="profile-field profile-field-full"><span>รายละเอียด</span><textarea name="description" rows="3"></textarea></label>
            <label class="profile-check profile-field-full"><input type="checkbox" name="is_current" value="1"><span>ยังทำอยู่ในปัจจุบัน</span></label>
            <button class="profile-btn profile-btn-primary profile-field-full" type="submit">เพิ่มประสบการณ์</button>
        </form>
        <div class="profile-manage-list">
            <?php foreach ($portfolio['experiences'] as $item): ?>
                <article><div><strong><?php echo h($item['position']); ?></strong><p><?php echo h($item['organization']); ?> · <?php echo h(portfolioDateLabel($item['started_at'])); ?></p></div><?php renderDeleteButton('experience', $item['id']); ?></article>
            <?php endforeach; ?>
            <?php if (!$portfolio['experiences']): ?><p class="profile-empty">ยังไม่มีประสบการณ์</p><?php endif; ?>
        </div>
    </section>

    <section class="profile-panel profile-manage-wide">
        <div class="profile-section-title"><span>03</span><h3>กิจกรรม / ความสำเร็จ</h3></div>
        <form method="post" class="profile-form-grid">
            <input type="hidden" name="action" value="add_activity">
            <label class="profile-field"><span>ประเภท</span><select name="activity_type"><option value="activity">กิจกรรม</option><option value="competition">การแข่งขัน</option><option value="award">รางวัล</option><option value="volunteer">จิตอาสา</option></select></label>
            <label class="profile-field"><span>ชื่อรายการ *</span><input name="title" required placeholder="ชื่อกิจกรรมหรือรางวัล"></label>
            <label class="profile-field"><span>องค์กร</span><input name="organization" placeholder="หน่วยงานผู้จัด"></label>
            <label class="profile-field"><span>บทบาท</span><input name="role_name" placeholder="ผู้เข้าร่วม / ประธานโครงการ"></label>
            <label class="profile-field"><span>วันที่</span><input name="activity_date" type="date"></label>
            <label class="profile-field"><span>ลิงก์อ้างอิง</span><input name="reference_url" type="url" placeholder="https://"></label>
            <label class="profile-field profile-field-full"><span>รายละเอียด</span><textarea name="description" rows="3"></textarea></label>
            <button class="profile-btn profile-btn-primary profile-field-full" type="submit">เพิ่มกิจกรรมหรือความสำเร็จ</button>
        </form>
        <div class="profile-manage-list">
            <?php foreach ($portfolio['activities'] as $item): ?>
                <article><div><strong><?php echo h($item['title']); ?></strong><p><?php echo h($item['organization']); ?> · <?php echo h(portfolioDateLabel($item['activity_date'])); ?></p></div><?php renderDeleteButton('activity', $item['id']); ?></article>
            <?php endforeach; ?>
            <?php if (!$portfolio['activities']): ?><p class="profile-empty">ยังไม่มีกิจกรรมหรือความสำเร็จ</p><?php endif; ?>
        </div>
    </section>
</div>
<?php
$content = ob_get_clean();
renderStudentLayout('จัดการ Portfolio', $content);
