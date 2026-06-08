<?php

require_once __DIR__ . '/../classes/ViewHelper.php';

function renderStudentLayout($title, $content)
{
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo h($title); ?> | <?php echo APP_NAME; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="../assets/css/style.css">
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4">
                <div>
                    <p class="text-sm font-medium text-teal-700">Student Portal</p>
                    <h1 class="text-xl font-bold"><?php echo h($title); ?></h1>
                </div>
                <nav class="flex items-center gap-2">
                    <a href="dashboard.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">หน้าแรก</a>
                    <a href="logout.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">ออกจากระบบ</a>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-6xl px-4 py-8">
            <?php echo $content; ?>
        </main>
    </body>
    </html>
    <?php
}
