<?php
session_start();
// Configurazione path con .htaccess
define('BASE_PATH', '/Progetto5H');
define('BASE_URL', 'http://localhost/Progetto5H');

// Helper function per creare URL puliti
function url($path = '') {
    return BASE_PATH . '/' . ltrim($path, '/');
}

// Helper per asset (CSS, JS, immagini)
function asset($path = '') {
    return BASE_PATH . '/public/assets/' . ltrim($path, '/');
}
