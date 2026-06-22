<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/StudentProfile.php';

$studentModel = new Student($conn);
$profileModel = new StudentProfile($conn);
$ownerStudentId = Session::studentId();
$targetStudentId = isset($_GET['id']) ? (int) $_GET['id'] : $ownerStudentId;
$student = $studentModel->find($targetStudentId);

if (!$student) {
    header('Location: profile.php');
    exit;
}

$isOwner = $ownerStudentId === $targetStudentId;
$profileVisibility = isset($student['profile_visibility']) ? $student['profile_visibility'] : 'members';

if (!$isOwner && $profileVisibility === 'private') {
    $isInLineage = $studentModel->isInLineage($ownerStudentId, $targetStudentId);
    if (!$isInLineage) {
        Session::flash('error', 'โปรไฟล์นี้ตั้งค่าเป็นส่วนตัว');
        header('Location: profile.php');
        exit;
    }
}

$portfolio = $profileModel->getPortfolio($targetStudentId);
$lineage = $studentModel->getLineage($targetStudentId);
$ancestors = $lineage ? $lineage['ancestors'] : [];
$descendants = $lineage ? $lineage['descendants'] : [];

if (!$isOwner) {
    if (empty($student['profile_image_visible'])) {
        $student['profile_image'] = '';
    }
    foreach (['projects', 'experiences', 'activities'] as $key) {
        $portfolio[$key] = array_values(array_filter($portfolio[$key], function ($item) {
            return !isset($item['is_visible']) || (int) $item['is_visible'] === 1;
        }));
    }
}

function profileValue($value, $fallback = 'ยังไม่ระบุ')
{
    return trim((string) $value) !== '' ? $value : $fallback;
}

function profilePhoneFormat($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    return strlen($digits) === 10 ? substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4) : $digits;
}

function profileExternalUrl($url)
{
    $url = trim((string) $url);
    return filter_var($url, FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $url) ? $url : '';
}

function profileEducationLabel($student)
{
    $status = isset($student['education_status']) ? $student['education_status'] : 'unspecified';
    if ($status === 'graduated') {
        return 'ศิษย์เก่า' . (!empty($student['graduation_year']) ? ' · สำเร็จการศึกษา ' . $student['graduation_year'] : '');
    }
    if ($status === 'studying') {
        return 'นักศึกษาปัจจุบัน' . (!empty($student['current_study_year']) ? ' · ชั้นปีที่ ' . $student['current_study_year'] : '');
    }
    if ($status === 'on_leave') {
        return 'พักการศึกษา';
    }
    return 'สมาชิกเครือข่ายศิษย์เก่า';
}

function profileEmploymentLabel($status)
{
    $labels = [
        'looking_for_internship' => 'กำลังมองหาที่ฝึกงาน',
        'looking_for_work' => 'เปิดรับโอกาสทำงาน',
        'employed' => 'กำลังทำงาน',
        'freelance' => 'รับงาน Freelance',
        'business_owner' => 'เจ้าของธุรกิจ',
        'not_available' => 'ยังไม่เปิดรับโอกาส',
    ];
    return isset($labels[$status]) ? $labels[$status] : '';
}

function profileDateRange($item)
{
    $start = !empty($item['started_at']) ? date('m/Y', strtotime($item['started_at'])) : '';
    $end = !empty($item['is_current']) ? 'ปัจจุบัน' : (!empty($item['ended_at']) ? date('m/Y', strtotime($item['ended_at'])) : '');
    return trim($start . ($start && $end ? ' – ' : '') . $end);
}

function profileSectionAllowed($student, $field, $isOwner)
{
    return $isOwner || !isset($student[$field]) || (int) $student[$field] === 1;
}

function profileEmpty($message, $isOwner, $manageUrl)
{
    ?>
    <div class="profile-empty-state">
        <p><?php echo h($message); ?></p>
        <?php if ($isOwner): ?><a href="<?php echo h($manageUrl); ?>">เพิ่มข้อมูล</a><?php endif; ?>
    </div>
    <?php
}

$displayName = trim($student['first_name'] . ' ' . $student['last_name']);
$headline = profileValue($student['headline'], profileEducationLabel($student));
$employmentLabel = profileEmploymentLabel(isset($student['employment_status']) ? $student['employment_status'] : '');
$contactItems = [];

if (($isOwner || !empty($student['email_visible'])) && !empty($student['email'])) {
    $contactItems[] = ['label' => 'Email', 'value' => $student['email'], 'url' => 'mailto:' . $student['email']];
}
if (($isOwner || !empty($student['phone_visible'])) && !empty($student['phone'])) {
    $contactItems[] = ['label' => 'โทรศัพท์', 'value' => profilePhoneFormat($student['phone']), 'url' => 'tel:' . preg_replace('/\D+/', '', $student['phone'])];
}
if (($isOwner || !empty($student['line_id_contact_visible'])) && !empty($student['line_id_contact'])) {
    $contactItems[] = ['label' => 'LINE', 'value' => $student['line_id_contact'], 'url' => ''];
}
foreach ([['Website', 'website_url'], ['GitHub', 'github_url'], ['LinkedIn', 'linkedin_url']] as $config) {
    $url = profileExternalUrl(isset($student[$config[1]]) ? $student[$config[1]] : '');
    if ($url !== '') {
        $contactItems[] = ['label' => $config[0], 'value' => preg_replace('/^https?:\/\/(www\.)?/i', '', rtrim($url, '/')), 'url' => $url];
    }
}

$completionFields = ['profile_image', 'headline', 'bio', 'education_status', 'employment_status', 'phone'];
$completed = 0;
foreach ($completionFields as $field) {
    if (!empty($student[$field]) && $student[$field] !== 'unspecified') {
        $completed++;
    }
}
if ($portfolio['skills']) $completed++;
if ($portfolio['projects']) $completed++;
$completionPercent = (int) round(($completed / 8) * 100);

ob_start();
?>
<section class="student-profile-hero">
    <div class="profile-hero-main">
        <div class="profile-avatar-wrap">
            <?php echo renderStudentAvatar($student, 'profile-avatar'); ?>
            <span class="profile-online-dot"></span>
        </div>
        <div class="profile-identity">
            <span class="profile-kicker"><?php echo h(profileEducationLabel($student)); ?></span>
            <h2><?php echo h(profileValue($displayName, 'สมาชิก Alumni')); ?></h2>
            <p class="profile-headline"><?php echo h($headline); ?></p>
            <p class="profile-major"><?php echo h(profileValue($student['faculty'])); ?> · <?php echo h(profileValue($student['major'])); ?></p>
            <div class="profile-badges">
                <?php if ($employmentLabel): ?><span><?php echo h($employmentLabel); ?></span><?php endif; ?>
                <?php if (!empty($student['generation']) && ($isOwner || !empty($student['generation_visible']))): ?><span>รุ่น <?php echo h($student['generation']); ?></span><?php endif; ?>
                <?php if (!empty($student['work_location'])): ?><span><?php echo h($student['work_location']); ?></span><?php endif; ?>
            </div>
            <?php if ($isOwner): ?>
                <div class="profile-actions">
                    <a href="edit-profile.php" class="profile-btn profile-btn-primary">แก้ไขโปรไฟล์</a>
                    <a href="portfolio-manage.php" class="profile-btn">จัดการ Portfolio</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="profile-stat-grid">
        <article><strong><?php echo count($portfolio['projects']); ?></strong><span>ผลงาน</span></article>
        <article><strong><?php echo count($portfolio['skills']); ?></strong><span>ทักษะ</span></article>
        <article><strong><?php echo count($portfolio['activities']); ?></strong><span>กิจกรรม</span></article>
        <article><strong><?php echo count($ancestors) + count($descendants); ?></strong><span>เครือข่ายสายรหัส</span></article>
    </div>
</section>

<?php if ($isOwner): ?>
<section class="profile-completion">
    <div><span>ความสมบูรณ์ของโปรไฟล์</span><strong><?php echo $completionPercent; ?>%</strong></div>
    <div class="profile-progress"><span style="width: <?php echo $completionPercent; ?>%"></span></div>
</section>
<?php endif; ?>

<div class="student-profile-grid">
    <div class="profile-main-column">
        <?php if (profileSectionAllowed($student, 'about_visible', $isOwner)): ?>
        <section class="profile-panel">
            <div class="profile-section-title"><span>01</span><h3>เกี่ยวกับฉัน</h3></div>
            <?php if (!empty($student['bio'])): ?><p class="profile-body-copy"><?php echo nl2br(h($student['bio'])); ?></p><?php else: ?><?php profileEmpty('ยังไม่มีข้อมูลแนะนำตัว', $isOwner, 'edit-profile.php'); ?><?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (profileSectionAllowed($student, 'projects_visible', $isOwner)): ?>
        <section class="profile-panel">
            <div class="profile-section-title"><span>02</span><h3>ผลงานเด่น</h3></div>
            <?php if ($portfolio['projects']): ?>
                <div class="profile-project-grid">
                    <?php foreach ($portfolio['projects'] as $project): ?>
                        <article class="profile-project-card">
                            <div class="profile-project-index">&lt;/&gt;</div>
                            <div><h4><?php echo h($project['title']); ?></h4><p><?php echo h($project['description']); ?></p></div>
                            <?php if (!empty($project['technologies'])): ?><div class="profile-tags"><?php foreach (array_filter(array_map('trim', explode(',', $project['technologies']))) as $tech): ?><span><?php echo h($tech); ?></span><?php endforeach; ?></div><?php endif; ?>
                            <div class="profile-card-links">
                                <?php if (profileExternalUrl($project['project_url'])): ?><a target="_blank" rel="noopener" href="<?php echo h($project['project_url']); ?>">ดูผลงาน ↗</a><?php endif; ?>
                                <?php if (profileExternalUrl($project['repository_url'])): ?><a target="_blank" rel="noopener" href="<?php echo h($project['repository_url']); ?>">Source ↗</a><?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><?php profileEmpty('ยังไม่มีผลงานใน Portfolio', $isOwner, 'portfolio-manage.php'); ?><?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (profileSectionAllowed($student, 'experiences_visible', $isOwner)): ?>
        <section class="profile-panel">
            <div class="profile-section-title"><span>03</span><h3>ประสบการณ์</h3></div>
            <?php if ($portfolio['experiences']): ?>
                <div class="profile-timeline">
                    <?php foreach ($portfolio['experiences'] as $experience): ?>
                        <article><span class="profile-timeline-dot"></span><div><small><?php echo h(profileDateRange($experience)); ?></small><h4><?php echo h($experience['position']); ?></h4><strong><?php echo h($experience['organization']); ?></strong><p><?php echo nl2br(h($experience['description'])); ?></p></div></article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><?php profileEmpty('ยังไม่มีข้อมูลประสบการณ์', $isOwner, 'portfolio-manage.php'); ?><?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (profileSectionAllowed($student, 'activities_visible', $isOwner)): ?>
        <section class="profile-panel">
            <div class="profile-section-title"><span>04</span><h3>กิจกรรมและความสำเร็จ</h3></div>
            <?php if ($portfolio['activities']): ?>
                <div class="profile-activity-list">
                    <?php foreach ($portfolio['activities'] as $activity): ?>
                        <article><div class="profile-activity-icon">★</div><div><small><?php echo h($activity['activity_type']); ?><?php if ($activity['activity_date']): ?> · <?php echo h(date('Y', strtotime($activity['activity_date'])) + 543); ?><?php endif; ?></small><h4><?php echo h($activity['title']); ?></h4><p><?php echo h($activity['organization']); ?><?php if ($activity['role_name']): ?> · <?php echo h($activity['role_name']); ?><?php endif; ?></p></div></article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?><?php profileEmpty('ยังไม่มีกิจกรรมหรือความสำเร็จ', $isOwner, 'portfolio-manage.php'); ?><?php endif; ?>
        </section>
        <?php endif; ?>
    </div>

    <aside class="profile-side-column">
        <?php if (profileSectionAllowed($student, 'education_visible', $isOwner)): ?>
        <section class="profile-panel">
            <div class="profile-section-title"><span>EDU</span><h3>การศึกษา</h3></div>
            <dl class="profile-detail-list">
                <div><dt>หลักสูตร</dt><dd><?php echo h(profileValue($student['major'])); ?></dd></div>
                <div><dt>คณะ</dt><dd><?php echo h(profileValue($student['faculty'])); ?></dd></div>
                <?php if ($student['education_status'] === 'studying'): ?><div><dt>ชั้นปี</dt><dd><?php echo h(profileValue($student['current_study_year'])); ?></dd></div><?php endif; ?>
                <?php if ($student['education_status'] === 'graduated'): ?><div><dt>ปีที่จบ</dt><dd><?php echo h(profileValue($student['graduation_year'])); ?></dd></div><?php endif; ?>
            </dl>
        </section>
        <?php endif; ?>

        <?php if (profileSectionAllowed($student, 'skills_visible', $isOwner)): ?>
        <section class="profile-panel">
            <div class="profile-section-title"><span>SKL</span><h3>ทักษะ</h3></div>
            <?php if ($portfolio['skills']): ?><div class="profile-tags profile-skill-tags"><?php foreach ($portfolio['skills'] as $skill): ?><span><?php echo h($skill['skill_name']); ?></span><?php endforeach; ?></div><?php else: ?><?php profileEmpty('ยังไม่ได้ระบุทักษะ', $isOwner, 'edit-profile.php'); ?><?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (profileSectionAllowed($student, 'employment_visible', $isOwner) && (!empty($student['current_position']) || !empty($student['current_company']))): ?>
        <section class="profile-panel profile-current-work">
            <div class="profile-section-title"><span>JOB</span><h3>ปัจจุบัน</h3></div>
            <strong><?php echo h(profileValue($student['current_position'])); ?></strong><p><?php echo h($student['current_company']); ?></p><small><?php echo h($student['work_location']); ?></small>
        </section>
        <?php endif; ?>

        <section class="profile-panel">
            <div class="profile-section-title"><span>NET</span><h3>สายรหัส</h3></div>
            <div class="profile-lineage-stats"><div><strong><?php echo count($ancestors); ?></strong><span>รุ่นพี่</span></div><div><strong><?php echo count($descendants); ?></strong><span>รุ่นน้อง</span></div></div>
            <a href="tree.php" class="profile-wide-link">ดูแผนผังสายรหัส →</a>
            <?php if ($isOwner): ?><a href="line-manage.php" class="profile-wide-link">ผูกหรือแก้ไขสายรหัส →</a><?php endif; ?>
        </section>

        <section class="profile-panel">
            <div class="profile-section-title"><span>CON</span><h3>ช่องทางติดต่อ</h3></div>
            <?php if ($contactItems): ?><div class="profile-contact-list"><?php foreach ($contactItems as $contact): ?><div><span><?php echo h($contact['label']); ?></span><?php if ($contact['url']): ?><a href="<?php echo h($contact['url']); ?>" <?php echo strpos($contact['url'], 'http') === 0 ? 'target="_blank" rel="noopener"' : ''; ?>><?php echo h($contact['value']); ?></a><?php else: ?><strong><?php echo h($contact['value']); ?></strong><?php endif; ?></div><?php endforeach; ?></div><?php else: ?><?php profileEmpty('ยังไม่มีช่องทางติดต่อที่เปิดเผย', $isOwner, 'edit-profile.php'); ?><?php endif; ?>
        </section>
    </aside>
</div>
<?php
$content = ob_get_clean();
renderStudentLayout($isOwner ? 'โปรไฟล์ของฉัน' : $displayName, $content);
