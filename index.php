<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Session.php';

Session::start();

if (Session::isAdminLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

if (Session::isStudentLoggedIn()) {
    header('Location: student/dashboard.php');
    exit;
}

$techCssVersion = filemtime(__DIR__ . '/assets/css/tech-connection.css');
$techJsVersion = filemtime(__DIR__ . '/assets/js/tech-connection.js');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/tech-connection.css?v=<?php echo htmlspecialchars((string) $techCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body class="student-tech-page min-h-screen bg-slate-100 text-slate-900">
    <canvas id="tcParticleCanvas" aria-hidden="true"></canvas>
    <main class="mx-auto flex min-h-screen max-w-5xl items-center px-4 py-10">
        <section class="w-full">
            <div class="mb-8 text-center">
                <p class="text-sm font-semibold text-teal-700">CIT Alumni</p>
                <h1 class="mt-2 text-3xl font-bold">Alumni Code Line</h1>
                <p class="mt-2 text-sm text-slate-600">เลือกประเภทการเข้าสู่ระบบ</p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <a href="student/login.php" class="rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200 hover:ring-teal-700">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-md bg-slate-50 text-teal-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422A12.083 12.083 0 0119 15.5c0 1.657-3.134 3-7 3s-7-1.343-7-3c0-1.01.543-2.13.84-4.922L12 14z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">นักศึกษา</h2>
                    <p class="mt-2 text-sm text-slate-600">เข้าสู่ระบบด้วยรหัสนักศึกษาและรหัสผ่านที่ตั้งไว้</p>
                </a>

                <a href="admin/login.php" class="rounded-lg bg-white p-8 shadow-sm ring-1 ring-slate-200 hover:ring-teal-700">
                    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-md bg-slate-50 text-teal-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c1.657 0 3-1.343 3-3V6a3 3 0 10-6 0v2c0 1.657 1.343 3 3 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 11h14l-1 10H6L5 11z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold">ผู้ดูแลระบบ</h2>
                    <p class="mt-2 text-sm text-slate-600">เข้าสู่ระบบเพื่อจัดการข้อมูลนักศึกษาและสายรหัส</p>
                </a>
            </div>
        </section>
    </main>
    <script src="assets/js/tech-connection.js?v=<?php echo htmlspecialchars((string) $techJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
