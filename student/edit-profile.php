<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/StudentProfile.php';

$studentModel = new Student($conn);
$profileModel = new StudentProfile($conn);
$studentId = Session::studentId();
$student = $studentModel->find($studentId);
$success = null;
$error = null;

if (!$student) {
    Session::logout();
    header('Location: login.php');
    exit;
}

function uploadProfileImage($file, $studentCode)
{
    if (!isset($file) || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('ไม่สามารถอัปโหลดรูปภาพได้');
    }

    if ((int) $file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('รูปภาพต้องมีขนาดไม่เกิน 2MB');
    }

    $imageInfo = getimagesize($file['tmp_name']);

    if ($imageInfo === false) {
        throw new RuntimeException('กรุณาเลือกไฟล์รูปภาพเท่านั้น');
    }

    $allowedTypes = [
        IMAGETYPE_JPEG => ['extension' => 'jpg', 'mime' => 'image/jpeg'],
        IMAGETYPE_PNG => ['extension' => 'png', 'mime' => 'image/png'],
    ];

    if (defined('IMAGETYPE_WEBP')) {
        $allowedTypes[IMAGETYPE_WEBP] = ['extension' => 'webp', 'mime' => 'image/webp'];
    }

    if (!isset($allowedTypes[$imageInfo[2]])) {
        throw new RuntimeException('รองรับเฉพาะไฟล์ JPG, PNG หรือ WEBP');
    }

    $imageType = $allowedTypes[$imageInfo[2]];
    $detectedMime = isset($imageInfo['mime']) ? strtolower((string) $imageInfo['mime']) : '';

    if ($detectedMime !== $imageType['mime']) {
        throw new RuntimeException('ชนิดไฟล์รูปภาพไม่ตรงกับข้อมูลภายในไฟล์');
    }

    if (defined('IMAGETYPE_WEBP') && $imageInfo[2] === IMAGETYPE_WEBP) {
        if (!function_exists('imagecreatefromwebp')) {
            throw new RuntimeException('เซิร์ฟเวอร์ยังไม่รองรับการประมวลผลไฟล์ WEBP');
        }

        $webpImage = @imagecreatefromwebp($file['tmp_name']);

        if ($webpImage === false) {
            throw new RuntimeException('ไฟล์ WEBP เสียหายหรืออยู่ในรูปแบบที่ไม่รองรับ');
        }

        imagedestroy($webpImage);
    }

    $uploadDir = __DIR__ . '/../assets/uploads/students';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์สำหรับเก็บรูปภาพได้');
    }

    $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $studentCode);
    $fileName = $safeCode . '-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $imageType['extension'];
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('ไม่สามารถบันทึกรูปภาพได้');
    }

    return 'assets/uploads/students/' . $fileName;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $requiredProfileFields = [
            'first_name' => 'ชื่อ',
            'last_name' => 'นามสกุล',
            'faculty' => 'คณะ',
            'major' => 'สาขา',
        ];

        foreach ($requiredProfileFields as $field => $label) {
            if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
                throw new RuntimeException('กรุณากรอก' . $label);
            }
        }

        $_POST['profile_image'] = isset($student['profile_image']) ? $student['profile_image'] : '';
        $uploadedProfileImage = uploadProfileImage(isset($_FILES['profile_image_file']) ? $_FILES['profile_image_file'] : null, $student['student_code']);

        if ($uploadedProfileImage !== null) {
            $_POST['profile_image'] = $uploadedProfileImage;
        }

        $studentModel->updateOwnProfile($studentId, $_POST);
        $profileModel->updateOverview($studentId, $_POST);
        $success = 'บันทึกข้อมูลเรียบร้อยแล้ว';
        $student = $studentModel->find($studentId);
    } catch (Exception $exception) {
        $error = $exception->getMessage();
    }
}

function checkedField($student, $field)
{
    return !empty($student[$field]) ? 'checked' : '';
}

function formatPhoneForInput($phone)
{
    $digits = preg_replace('/\D+/', '', (string) $phone);

    if (strlen($digits) <= 3) {
        return $digits;
    }

    if (strlen($digits) <= 6) {
        return substr($digits, 0, 3) . '-' . substr($digits, 3);
    }

    return substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
}

$facultyOptions = [
    'วิทยาศาสตร์และเทคโนโลยี',
];

$majorOptions = [
    'วิทยาการคอมพิวเตอร์',
    'เทคโนโลยีมัลติมีเดียและแอนิเมชัน',
    'เทคโนโลยีสารสนเทศ',
];

$portfolio = $profileModel->getPortfolio($studentId);
$skillValue = implode(', ', array_column($portfolio['skills'], 'skill_name'));

ob_start();
?>
<?php if ($success): ?>
    <div class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"><?php echo h($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo h($error); ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <div class="mb-6 flex items-center gap-4">
        <?php echo renderStudentAvatar($student, 'h-20 w-20'); ?>
        <div>
            <p class="text-sm text-slate-500">แก้ไขข้อมูลส่วนตัว</p>
            <h2 class="text-xl font-bold"><?php echo h($student['student_code']); ?></h2>
        </div>
    </div>

    <div class="grid gap-5 md:grid-cols-2">
        <label>
            <span class="mb-2 block text-sm font-medium">ชื่อ <span class="text-red-400" aria-hidden="true">*</span></span>
            <input name="first_name" required value="<?php echo h($student['first_name']); ?>" placeholder="ชื่อจริง" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </label>
        <label>
            <span class="mb-2 block text-sm font-medium">นามสกุล <span class="text-red-400" aria-hidden="true">*</span></span>
            <input name="last_name" required value="<?php echo h($student['last_name']); ?>" placeholder="นามสกุล" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </label>
        <label>
            <span class="mb-2 block text-sm font-medium">ชื่อเล่น</span>
            <input name="nickname" value="<?php echo h($student['nickname']); ?>" placeholder="ชื่อเล่น" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </label>
        <label>
            <span class="mb-2 block text-sm font-medium">คณะ <span class="text-red-400" aria-hidden="true">*</span></span>
            <select name="faculty" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">เลือกคณะ</option>
                <?php foreach ($facultyOptions as $faculty): ?>
                    <option value="<?php echo h($faculty); ?>" <?php echo (isset($student['faculty']) && $student['faculty'] === $faculty) ? 'selected' : ''; ?>><?php echo h($faculty); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span class="mb-2 block text-sm font-medium">สาขา <span class="text-red-400" aria-hidden="true">*</span></span>
            <select name="major" required class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">เลือกสาขา</option>
                <?php foreach ($majorOptions as $major): ?>
                    <option value="<?php echo h($major); ?>" <?php echo (isset($student['major']) && $student['major'] === $major) ? 'selected' : ''; ?>><?php echo h($major); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" maxlength="12" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" value="<?php echo h(formatPhoneForInput($student['phone'])); ?>" placeholder="099-999-9999" title="กรุณากรอกเบอร์โทร 10 หลัก เช่น 099-999-9999" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input type="email" value="<?php echo h(isset($student['email']) ? $student['email'] : ''); ?>" placeholder="Email" readonly class="rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-500" title="Email ที่ใช้สมัครสมาชิก">
        <input name="facebook" value="<?php echo h($student['facebook']); ?>" placeholder="Facebook" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input name="instagram" value="<?php echo h($student['instagram']); ?>" placeholder="Instagram" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input name="line_id_contact" value="<?php echo h($student['line_id_contact']); ?>" placeholder="Line ID" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <div class="md:col-span-2">
            <label for="profile_image_file" class="mb-2 block text-sm font-medium">รูปโปรไฟล์</label>
            <input id="profile_image_file" name="profile_image_file" type="file" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-teal-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-teal-800">
            <p class="mt-2 text-xs text-slate-500">รองรับไฟล์ JPG, PNG และ WEBP ขนาดไม่เกิน 2MB</p>
            <p class="mt-2 text-xs text-slate-500">รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 2MB ถ้าไม่เลือกรูปใหม่ ระบบจะใช้รูปเดิม</p>
        </div>
    </div>

    <section class="mt-6 rounded-lg border border-slate-200 p-5">
        <div class="mb-5">
            <p class="text-sm font-semibold text-teal-700">Profile &amp; Portfolio</p>
            <h3 class="mt-1 text-lg font-semibold">ข้อมูลแนะนำตัวและสถานะปัจจุบัน</h3>
        </div>
        <div class="grid gap-5 md:grid-cols-2">
            <label>
                <span class="mb-2 block text-sm font-medium">สถานะการศึกษา</span>
                <select name="education_status" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    <?php foreach (['unspecified' => 'ยังไม่ระบุ', 'studying' => 'กำลังศึกษา', 'graduated' => 'สำเร็จการศึกษาแล้ว', 'on_leave' => 'พักการศึกษา'] as $value => $label): ?>
                        <option value="<?php echo h($value); ?>" <?php echo ($student['education_status'] ?? 'unspecified') === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">สถานะการทำงาน</span>
                <select name="employment_status" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    <?php foreach (['unspecified' => 'ยังไม่ระบุ', 'looking_for_internship' => 'กำลังหาที่ฝึกงาน', 'looking_for_work' => 'กำลังหางาน', 'employed' => 'กำลังทำงาน', 'freelance' => 'Freelance', 'business_owner' => 'เจ้าของธุรกิจ', 'not_available' => 'ยังไม่เปิดรับโอกาส'] as $value => $label): ?>
                        <option value="<?php echo h($value); ?>" <?php echo ($student['employment_status'] ?? 'unspecified') === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">ชั้นปีปัจจุบัน</span>
                <input name="current_study_year" type="number" min="1" max="8" value="<?php echo h($student['current_study_year'] ?? ''); ?>" placeholder="เช่น 3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">ปีที่คาดว่าจะจบ (พ.ศ.)</span>
                <input name="expected_graduation_year" type="number" min="2400" max="2700" value="<?php echo h($student['expected_graduation_year'] ?? ''); ?>" placeholder="เช่น 2569" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">ปีที่สำเร็จการศึกษา (พ.ศ.)</span>
                <input name="graduation_year" type="number" min="2400" max="2700" value="<?php echo h($student['graduation_year'] ?? ''); ?>" placeholder="กรอกเมื่อสำเร็จการศึกษาแล้ว" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">การมองเห็นโปรไฟล์</span>
                <select name="profile_visibility" class="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">
                    <?php foreach (['private' => 'เฉพาะฉัน', 'members' => 'สมาชิกที่เข้าสู่ระบบ', 'public' => 'สาธารณะ'] as $value => $label): ?>
                        <option value="<?php echo h($value); ?>" <?php echo ($student['profile_visibility'] ?? 'members') === $value ? 'selected' : ''; ?>><?php echo h($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="md:col-span-2">
                <span class="mb-2 block text-sm font-medium">Headline</span>
                <input name="headline" maxlength="180" value="<?php echo h($student['headline'] ?? ''); ?>" placeholder="เช่น นักศึกษาวิทยาการคอมพิวเตอร์ สนใจ Frontend Development" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label class="md:col-span-2">
                <span class="mb-2 block text-sm font-medium">เกี่ยวกับฉัน</span>
                <textarea name="bio" rows="5" placeholder="แนะนำตัว ความสนใจ และเป้าหมายของคุณ" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><?php echo h($student['bio'] ?? ''); ?></textarea>
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">ตำแหน่งปัจจุบัน</span>
                <input name="current_position" value="<?php echo h($student['current_position'] ?? ''); ?>" placeholder="เช่น Frontend Developer" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">บริษัท / องค์กร</span>
                <input name="current_company" value="<?php echo h($student['current_company'] ?? ''); ?>" placeholder="ชื่อบริษัทหรือองค์กร" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">สถานที่ทำงาน</span>
                <input name="work_location" value="<?php echo h($student['work_location'] ?? ''); ?>" placeholder="จังหวัด หรือรูปแบบ Remote/Hybrid" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">Website</span>
                <input name="website_url" type="url" value="<?php echo h($student['website_url'] ?? ''); ?>" placeholder="https://example.com" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">GitHub</span>
                <input name="github_url" type="url" value="<?php echo h($student['github_url'] ?? ''); ?>" placeholder="https://github.com/username" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label>
                <span class="mb-2 block text-sm font-medium">LinkedIn</span>
                <input name="linkedin_url" type="url" value="<?php echo h($student['linkedin_url'] ?? ''); ?>" placeholder="https://linkedin.com/in/username" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            </label>
            <label class="md:col-span-2">
                <span class="mb-2 block text-sm font-medium">ทักษะ</span>
                <textarea name="skills" rows="3" placeholder="HTML, CSS, JavaScript, UI/UX (คั่นแต่ละทักษะด้วยเครื่องหมายจุลภาค)" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"><?php echo h($skillValue); ?></textarea>
            </label>
        </div>
    </section>

    <section class="mt-6 rounded-md bg-slate-50 p-4">
        <h3 class="font-semibold">การเปิดเผยข้อมูล</h3>
        <p class="mt-1 text-sm text-slate-600">ชื่อจริง นามสกุล ชื่อเล่น คณะ และสาขา จะแสดงเสมอ</p>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <?php
            $privacyFields = [
                'student_code_visible' => 'เปิดเผยรหัสนักศึกษา',
                'generation_visible' => 'เปิดเผยปีการศึกษา',
                'phone_visible' => 'เปิดเผยเบอร์โทร',
                'email_visible' => 'เปิดเผย Email',
                'facebook_visible' => 'เปิดเผย Facebook',
                'instagram_visible' => 'เปิดเผย Instagram',
                'line_id_contact_visible' => 'เปิดเผย Line ID',
                'profile_image_visible' => 'เปิดเผยรูปโปรไฟล์',
                'about_visible' => 'แสดงข้อมูลเกี่ยวกับฉัน',
                'education_visible' => 'แสดงข้อมูลการศึกษา',
                'employment_visible' => 'แสดงข้อมูลการทำงาน',
                'skills_visible' => 'แสดงทักษะ',
                'projects_visible' => 'แสดงผลงาน',
                'experiences_visible' => 'แสดงประสบการณ์',
                'activities_visible' => 'แสดงกิจกรรมและความสำเร็จ',
            ];
            ?>
            <?php foreach ($privacyFields as $field => $label): ?>
                <label class="privacy-toggle">
                    <input type="checkbox" name="<?php echo h($field); ?>" value="1" <?php echo checkedField($student, $field); ?>>
                    <span class="privacy-toggle-track" aria-hidden="true">
                        <span class="privacy-toggle-thumb"></span>
                    </span>
                    <span class="privacy-toggle-label"><?php echo h($label); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="mt-6 flex justify-end gap-2">
        <a href="profile.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">กลับ</a>
        <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">บันทึก</button>
    </div>
</form>
<script>
(function () {
    var phoneInput = document.getElementById('phone');

    if (!phoneInput) {
        return;
    }

    phoneInput.addEventListener('input', function () {
        var digits = phoneInput.value.replace(/\D/g, '').slice(0, 10);
        var formatted = digits.slice(0, 3);

        if (digits.length > 3) {
            formatted += '-' + digits.slice(3, 6);
        }

        if (digits.length > 6) {
            formatted += '-' + digits.slice(6, 10);
        }

        phoneInput.value = formatted;
    });
})();
</script>
<?php
$content = ob_get_clean();

renderStudentLayout('แก้ไขข้อมูลส่วนตัว', $content);
