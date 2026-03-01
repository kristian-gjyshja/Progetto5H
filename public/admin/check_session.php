<?php
require_once __DIR__ . '/../../app/config.php';
include __DIR__ . '/../../app/dal/db.php';

$allowed_roles = ['admin', 'user'];

if (!isset($_SESSION['email'], $_SESSION['ruolo'])) {
    header('Location: ' . url('public/login/login.php?error=sessione_scaduta'));
    exit();
}

if (!in_array($_SESSION['ruolo'], $allowed_roles)) {
    http_response_code(401);
    exit('Unauthorized');
}
