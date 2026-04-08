<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/bootstrap.php';
SiteAuth::logout();
header('Location: login');
exit;
