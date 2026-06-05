<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Session.php';

Session::start();

if (Session::isLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

header('Location: admin/login.php');
exit;
