<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Session.php';
require_once __DIR__ . '/../classes/StudentUser.php';
require_once __DIR__ . '/../classes/Student.php';

Session::start();

if (Session::isStudentLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = Session::flash('error');
$techCssVersion = filemtime(__DIR__ . '/../assets/css/tech-connection.css');
$techJsVersion = filemtime(__DIR__ . '/../assets/js/tech-connection.js');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    $studentUser = new StudentUser($conn);
    $studentModel = new Student($conn);
    $authenticatedUser = $studentUser->authenticate($username, $password);

    if ($authenticatedUser) {
        Session::studentLogin($authenticatedUser);
        header('Location: dashboard.php');
        exit;
    }

    $error = $studentModel->findByCode($username) ? 'รหัสผ่านไม่ถูกต้อง' : 'รหัสนักศึกษาไม่ถูกต้อง';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/tech-connection.css?v=<?php echo htmlspecialchars((string) $techCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="student-tech-page min-h-screen bg-slate-100 text-slate-900">
    <canvas id="tcParticleCanvas" aria-hidden="true"></canvas>
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-md rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <div class="mb-8">
                <p class="text-sm font-medium text-teal-700">Student Portal</p>
                <h1 class="mt-2 text-2xl font-bold">เข้าสู่ระบบนักศึกษา</h1>
            </div>

            <?php if ($error): ?>
                <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" class="space-y-5">
                <div>
                    <label for="username" class="mb-2 block text-sm font-medium">รหัสนักศึกษา</label>
                    <input id="username" name="username" type="text" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium">รหัสผ่าน</label>
                    <input id="password" name="password" type="password" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100">
                    <p class="mt-2 text-xs text-slate-500">ใช้รหัสผ่านที่ตั้งไว้ตอนสมัครสมาชิก</p>
                </div>
                <button type="submit" class="w-full rounded-md bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">เข้าสู่ระบบ</button>
            </form>
            <div class="mt-5 space-y-2 text-center text-sm">
                <a href="register.php" class="block font-semibold text-teal-700 hover:underline">สมัครสมาชิกนักศึกษา</a>
                <a href="../index.php" class="block text-slate-600 hover:text-teal-700">กลับหน้าเลือกประเภทผู้ใช้</a>
            </div>
        </section>
    </main>
    <script src="../assets/js/tech-connection.js?v=<?php echo htmlspecialchars((string) $techJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
