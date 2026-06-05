<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

ob_start();
?>
<section class="grid gap-4 md:grid-cols-3">
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">ระบบ</p>
        <h3 class="mt-2 text-xl font-bold">พร้อมใช้งาน</h3>
    </article>
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">Module</p>
        <h3 class="mt-2 text-xl font-bold">Students</h3>
    </article>
    <article class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <p class="text-sm text-slate-500">Login</p>
        <h3 class="mt-2 text-xl font-bold">Admin</h3>
    </article>
</section>

<section class="mt-6 rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h3 class="text-lg font-semibold">งานหลัก</h3>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <a href="students.php" class="rounded-md border border-slate-300 p-4 hover:bg-slate-50">
            <p class="font-semibold">จัดการนักศึกษา</p>
            <p class="mt-1 text-sm text-slate-600">เพิ่ม แก้ไข ลบ ค้นหา และดูสายรหัส</p>
        </a>
        <a href="student-import.php" class="rounded-md border border-slate-300 p-4 hover:bg-slate-50">
            <p class="font-semibold">Import CSV</p>
            <p class="mt-1 text-sm text-slate-600">นำเข้าข้อมูลและสร้างบัญชีผู้ใช้นักศึกษา</p>
        </a>
    </div>
</section>
<?php
$content = ob_get_clean();

renderAdminLayout('Dashboard', 'dashboard', $content);
