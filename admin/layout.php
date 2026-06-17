<?php

require_once __DIR__ . '/../classes/ViewHelper.php';

function renderAdminLayout($title, $activeMenu, $content, $extraHead = '', $extraScripts = '')
{
    $username = Session::adminUsername();
    $cssVersion = filemtime(__DIR__ . '/../assets/css/style.css');
    $menus = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php'],
        ['key' => 'students', 'label' => 'จัดการนักศึกษา', 'href' => 'students.php'],
        ['key' => 'import', 'label' => 'Import CSV', 'href' => 'student-import.php'],
    ];
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo h($title); ?> | <?php echo APP_NAME; ?></title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo h($cssVersion); ?>">
        <?php echo $extraHead; ?>
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <div class="admin-shell">
            <aside class="admin-sidebar">
                <div class="admin-brand">
                    <p class="admin-brand-kicker">CIT Alumni</p>
                    <h1>สายรหัส</h1>
                </div>

                <nav class="admin-nav" aria-label="Admin navigation">
                    <?php foreach ($menus as $menu): ?>
                        <a href="<?php echo h($menu['href']); ?>" class="admin-nav-link <?php echo $activeMenu === $menu['key'] ? 'active' : ''; ?>">
                            <?php echo h($menu['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="admin-sidebar-footer">
                    <p class="text-xs text-slate-500">เข้าสู่ระบบโดย</p>
                    <p class="mt-1 font-semibold"><?php echo h($username); ?></p>
                    <a href="logout.php" class="mt-4 block rounded-md border border-slate-300 px-3 py-2 text-center text-sm font-medium hover:bg-slate-50">ออกจากระบบ</a>
                </div>
            </aside>

            <div class="admin-main">
                <header class="admin-topbar">
                    <div>
                        <p class="text-sm font-medium text-teal-700">Admin Section</p>
                        <h2 class="text-2xl font-bold"><?php echo h($title); ?></h2>
                    </div>
                    <a href="logout.php" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50 md:hidden">ออกจากระบบ</a>
                </header>

                <main class="admin-content">
                    <?php echo $content; ?>
                </main>
            </div>
        </div>
        <?php echo $extraScripts; ?>
    </body>
    </html>
    <?php
}
