<?php

require_once __DIR__ . '/../classes/ViewHelper.php';

function renderStudentLayout($title, $content, $extraHead = '', $extraScripts = '')
{
    $cssVersion = filemtime(__DIR__ . '/../assets/css/style.css');
    $techCssVersion = filemtime(__DIR__ . '/../assets/css/tech-connection.css');
    $techJsVersion = filemtime(__DIR__ . '/../assets/js/tech-connection.js');
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo h($title); ?> | <?php echo APP_NAME; ?></title>
        <link rel="icon" type="image/png" href="../assets/images/scitec-logo.png">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo h($cssVersion); ?>">
        <link rel="stylesheet" href="../assets/css/tech-connection.css?v=<?php echo h($techCssVersion); ?>">
        <?php echo $extraHead; ?>
    </head>
    <body class="student-tech-page min-h-screen bg-slate-100 text-slate-900">
        <canvas id="tcParticleCanvas" aria-hidden="true"></canvas>

        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-teal-700">CIT Code Line</p>
                    <h1 class="text-xl font-bold"><?php echo h($title); ?></h1>
                </div>
                <nav class="flex flex-wrap items-center gap-2">
                    <a href="profile.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">โปรไฟล์ของฉัน</a>
                    <a href="tree.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">สายรหัส</a>
                    <a href="line-manage.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">ผูกสายรหัส</a>
                    <a href="edit-profile.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">แก้ไขข้อมูล</a>
                    <a href="portfolio-manage.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">Portfolio</a>
                    <a href="logout.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50">ออกจากระบบ</a>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-7xl px-4 py-8">
            <?php echo $content; ?>
        </main>
        <?php echo $extraScripts; ?>
        <script src="../assets/js/tech-connection.js?v=<?php echo h($techJsVersion); ?>"></script>
    </body>
    </html>
    <?php
}
