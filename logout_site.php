<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/SiteAuth.php';
SiteAuth::logout();
header('Location: login.php');
exit;
