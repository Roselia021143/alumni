<?php

require_once __DIR__ . '/auth.php';

$username = Session::adminUsername();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
            <div>
                <p class="text-sm font-medium text-teal-700">Alumni Code Line</p>
                <h1 class="text-xl font-bold">Admin Dashboard</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-600"><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="logout.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">
                    ออกจากระบบ
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">Phase 1 พร้อมใช้งาน</h2>
            <p class="mt-2 text-sm text-slate-600">
                ระบบเข้าสู่ระบบผู้ดูแล, การเชื่อมต่อฐานข้อมูล และการจัดการเซสชันถูกเตรียมไว้แล้ว
            </p>
        </section>
    </main>
</body>
</html>
