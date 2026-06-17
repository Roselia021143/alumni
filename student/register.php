<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/StudentUser.php';
require_once __DIR__ . '/../classes/ViewHelper.php';

Session::start();

if (Session::isStudentLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$studentCode = '';
$email = '';
$techCssVersion = filemtime(__DIR__ . '/../assets/css/tech-connection.css');
$techJsVersion = filemtime(__DIR__ . '/../assets/js/tech-connection.js');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentModel = new Student($conn);
    $studentUserModel = new StudentUser($conn);
    $studentCode = isset($_POST['student_code']) ? trim($_POST['student_code']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    try {
        if ($password === '' || $password !== $confirmPassword) {
            throw new RuntimeException('กรุณายืนยันรหัสผ่านให้ตรงกัน');
        }

        if (strlen($password) < 6) {
            throw new RuntimeException('รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
        }

        $studentModel->createRegistration($_POST, $password);
        $studentUser = $studentUserModel->findByUsername($studentCode);

        if (!$studentUser) {
            throw new RuntimeException('สมัครสำเร็จ แต่ไม่สามารถเข้าสู่ระบบอัตโนมัติได้');
        }

        Session::studentLogin($studentUser);
        Session::flash('success', 'สมัครสมาชิกเรียบร้อยแล้ว กรุณากรอกข้อมูลส่วนตัวเพิ่มเติม');
        header('Location: edit-profile.php');
        exit;
    } catch (Exception $exception) {
        $error = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิกนักศึกษา | <?php echo h(APP_NAME); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/tech-connection.css?v=<?php echo h($techCssVersion); ?>">
</head>
<body class="student-tech-page min-h-screen bg-slate-100 text-slate-900">
    <canvas id="tcParticleCanvas" aria-hidden="true"></canvas>
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-lg rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <div class="mb-8">
                <p class="text-sm font-medium text-teal-700">Student Portal</p>
                <h1 class="mt-2 text-2xl font-bold">สมัครสมาชิกนักศึกษา</h1>
                <p class="mt-2 text-sm text-slate-600">ใช้ข้อมูลเริ่มต้นเพียง 3 อย่าง หลังสมัครแล้วระบบจะพาไปกรอกโปรไฟล์ต่อ</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo h($error); ?></div>
            <?php endif; ?>

            <form method="post" class="space-y-5">
                <div>
                    <label for="student_code" class="mb-2 block text-sm font-medium">รหัสนักศึกษา</label>
                    <input id="student_code" name="student_code" value="<?php echo h($studentCode); ?>" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" value="<?php echo h($email); ?>" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-medium">รหัสผ่าน</label>
                    <input id="password" name="password" type="password" required minlength="6" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                </div>

                <div>
                    <label for="confirm_password" class="mb-2 block text-sm font-medium">ยืนยันรหัสผ่าน</label>
                    <input id="confirm_password" name="confirm_password" type="password" required minlength="6" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <a href="login.php" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">กลับ</a>
                    <button type="submit" class="rounded-md bg-teal-700 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-800">สมัครสมาชิก</button>
                </div>
            </form>
        </section>
    </main>
    <script src="../assets/js/tech-connection.js?v=<?php echo h($techJsVersion); ?>"></script>
</body>
</html>
