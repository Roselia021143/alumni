<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Session.php';

Session::logout();
header('Location: ../index.php');
exit;
