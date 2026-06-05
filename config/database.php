<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../classes/Database.php';

$database = new Database();
$conn = $database->connect();
