// _check_session
<?php
require_once __DIR__ . '/../app/config.php';
// La sessione viene avviata in app/config.php

// Ruoli consentiti nel sistema
$allowed_roles = ['admin', 'user'];

// Verifica se l'utente è loggato
if (!isset($_SESSION['email']) || !isset($_SESSION['ruolo'])) {
    header('Location:login.php?error=1');
    exit();
}

// Verifica che il ruolo dell'utente sia valido
if (!in_array($_SESSION['ruolo'], $allowed_roles)) {
    http_response_code(401);
    exit();
}
