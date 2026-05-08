<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn()) {
    header('Location: /' . currentRole() . '/dashboard.php');
    exit;
}
