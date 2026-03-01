<?php
require_once __DIR__ . '/../../app/config.php';
session_destroy();
header('Location: ' . url('public/login/login.php'));
exit;
