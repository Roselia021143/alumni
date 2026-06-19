<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Session.php';
require_once __DIR__ . '/classes/Student.php';

Session::start();

if (Session::isAdminLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

if (Session::isStudentLoggedIn()) {
    header('Location: student/dashboard.php');
    exit;
}

$studentModel = new Student($conn);
$publicStats = $studentModel->publicStats();

$landingCssVersion = filemtime(__DIR__ . '/assets/css/landing.css');
$techJsVersion = filemtime(__DIR__ . '/assets/js/tech-connection.js');
$landingJsVersion = filemtime(__DIR__ . '/assets/js/landing.js');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CIT Code Line ระบบจัดการข้อมูลนักศึกษาและสายรหัส">
    <title>CIT Code Line | Alumni System</title>
    <link rel="icon" type="image/png" href="assets/images/scitec-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css?v=<?php echo htmlspecialchars((string) $landingCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <canvas id="tcParticleCanvas" aria-hidden="true"></canvas>

    <header class="site-header">
        <a class="brand" href="#home" aria-label="CIT Code Line หน้าหลัก">
            <span class="brand-mark"><img src="assets/images/scitec-logo.png" alt=""></span>
            <span><strong>CIT Code Line</strong><small>Alumni System</small></span>
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mainNav" aria-label="เปิดเมนู">
            <span></span><span></span><span></span>
        </button>

        <nav id="mainNav" class="main-nav" aria-label="เมนูหลัก">
            <a class="active" href="#home">หน้าหลัก</a>
            <a href="#about">เกี่ยวกับเรา</a>
            <a href="#programs">สายรหัส</a>
            <a href="#stats">สถิติ</a>
            <a href="#contact">ติดต่อเรา</a>
        </nav>

        <a class="admin-link" href="admin/login.php">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3m-9 0h8a2 2 0 0 1 2 2v7H6v-7a2 2 0 0 1 2-2Z"/></svg>
            สำหรับแอดมิน
        </a>
    </header>

    <main id="home" class="page-shell">
        <section class="hero-content" aria-labelledby="heroTitle">
            <div class="hero-copy">
                <p class="eyebrow">ระบบจัดการข้อมูลนักศึกษาและสายรหัส</p>
                <h1 id="heroTitle">CIT Code Line</h1>
                <p class="hero-description">เชื่อมโยงรุ่นพี่ รุ่นน้อง สร้างเครือข่ายที่แข็งแกร่ง<br>คณะคอมพิวเตอร์และเทคโนโลยีสารสนเทศ</p>

                <div id="about" class="feature-list">
                    <article>
                        <span class="feature-icon"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8 0 2 2 4-4"/></svg></span>
                        <span><strong>เชื่อมโยงรุ่นพี่ รุ่นน้อง</strong><small>สร้างเครือข่ายที่ยั่งยืน</small></span>
                    </article>
                    <article>
                        <span class="feature-icon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Zm-3-10 2 2 4-4"/></svg></span>
                        <span><strong>ข้อมูลปลอดภัย</strong><small>เชื่อถือได้ 100%</small></span>
                    </article>
                    <article>
                        <span class="feature-icon"><svg viewBox="0 0 24 24"><path d="M3 3v18h18M7 16l4-4 3 3 6-8"/></svg></span>
                        <span><strong>ระบบทันสมัย</strong><small>ใช้งานง่าย รวดเร็ว</small></span>
                    </article>
                </div>
            </div>

            <section id="stats" class="stats-panel" aria-labelledby="statsTitle">
                <h2 id="statsTitle">สถิติที่น่าสนใจ</h2>
                <div class="stats-grid">
                    <article><svg viewBox="0 0 24 24"><path d="m3 8 9-5 9 5-9 5-9-5Zm3 3v5c3 2 9 2 12 0v-5"/></svg><span>นักศึกษาทั้งหมด</span><strong><?php echo number_format($publicStats['total_students']); ?></strong><small>คน</small></article>
                    <article><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m7.5-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm11.5 10v-2a4 4 0 0 0-3-3.87M15 3.13a4 4 0 0 1 0 7.75"/></svg><span>สายรหัสทั้งหมด</span><strong><?php echo number_format($publicStats['total_lines']); ?></strong><small>สาย</small></article>
                    <article><svg viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg><span>ความสัมพันธ์เชื่อมโยง</span><strong><?php echo number_format($publicStats['total_relationships']); ?></strong><small>ความสัมพันธ์</small></article>
                    <article><svg viewBox="0 0 24 24"><path d="M4 21V7l8-4 8 4v14M9 21v-5h6v5M8 10h1m6 0h1"/></svg><span>3 หลักสูตร</span><strong>100%</strong><small>ครอบคลุม</small></article>
                </div>
            </section>

            <section id="programs" class="programs" aria-labelledby="programsTitle">
                <div class="section-title"><span></span><h2 id="programsTitle">3 หลักสูตรของเรา</h2><span></span></div>
                <div class="program-grid">
                    <article><svg viewBox="0 0 24 24"><path d="M4 5h16v12H4V5Zm4 16h8m-4-4v4m-4-9 2-2-2-2m5 4h3"/></svg><strong>วิทยาการคอมพิวเตอร์</strong></article>
                    <article><svg viewBox="0 0 24 24"><path d="M4 6c0-2 4-3 8-3s8 1 8 3-4 3-8 3-8-1-8-3Zm0 0v6c0 2 4 3 8 3s8-1 8-3V6m-16 6v6c0 2 4 3 8 3s8-1 8-3v-6"/></svg><strong>เทคโนโลยีสารสนเทศ</strong></article>
                    <article><svg viewBox="0 0 24 24"><path d="M3 5h18v14H3V5Zm4 0v14M17 5v14M3 9h4m10 0h4M3 15h4m10 0h4"/></svg><strong>เทคโนโลยีมัลติมีเดีย<br>และแอนิเมชัน</strong></article>
                </div>
            </section>
        </section>

        <aside class="login-panel" aria-labelledby="loginTitle">
            <div class="login-heading">
                <span><svg viewBox="0 0 24 24"><path d="M7 10V7a5 5 0 0 1 10 0v3m-9 0h8a2 2 0 0 1 2 2v8H6v-8a2 2 0 0 1 2-2Z"/></svg></span>
                <h2 id="loginTitle">เข้าสู่ระบบ</h2>
            </div>

            <div class="login-tabs" role="tablist" aria-label="การเข้าใช้งาน">
                <span class="active" role="tab" aria-selected="true">เข้าสู่ระบบ</span>
                <a href="student/register.php" role="tab" aria-selected="false">สมัครสมาชิก</a>
            </div>

            <form action="student/login.php" method="post">
                <label class="input-wrap">
                    <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                    <span class="sr-only">รหัสนักศึกษา หรืออีเมล</span>
                    <input name="username" type="text" required autocomplete="username" placeholder="รหัสนักศึกษา / อีเมล">
                </label>
                <label class="input-wrap">
                    <svg viewBox="0 0 24 24"><path d="M7 11V7a5 5 0 0 1 10 0v4M5 11h14v10H5V11Z"/></svg>
                    <span class="sr-only">รหัสผ่าน</span>
                    <input id="landingPassword" name="password" type="password" required autocomplete="current-password" placeholder="รหัสผ่าน">
                    <button class="password-toggle" type="button" aria-label="แสดงรหัสผ่าน" aria-pressed="false"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/></svg></button>
                </label>

                <div class="form-options">
                    <label><input type="checkbox" name="remember" value="1"><span></span>จดจำฉัน</label>
                    <a href="student/login.php">ลืมรหัสผ่าน?</a>
                </div>

                <button class="submit-button" type="submit">เข้าสู่ระบบ</button>
            </form>

            <div class="register-note"><span></span><p>ยังไม่มีบัญชี? <a href="student/register.php">สมัครสมาชิก</a></p><span></span></div>
            <a class="admin-mobile-link" href="admin/login.php"><svg viewBox="0 0 24 24"><path d="M7 10V7a5 5 0 0 1 10 0v3m-9 0h8a2 2 0 0 1 2 2v7H6v-7a2 2 0 0 1 2-2Z"/></svg>สำหรับแอดมิน เข้าสู่ระบบ</a>
        </aside>
    </main>

    <footer id="contact" class="site-footer">
        <div><strong>CIT Code Line</strong><span>© 2026 Faculty of Computer and Information Technology.</span><span class="version">Version <?php echo htmlspecialchars(APP_VERSION, ENT_QUOTES, 'UTF-8'); ?></span></div>
        <div class="contact-list"><a href="mailto:cit@university.ac.th">cit@university.ac.th</a><a href="tel:021234567">02-123-4567</a></div>
    </footer>

    <script src="assets/js/tech-connection.js?v=<?php echo htmlspecialchars((string) $techJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script src="assets/js/landing.js?v=<?php echo htmlspecialchars((string) $landingJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>
