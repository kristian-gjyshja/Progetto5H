<?php
session_start();
// Configurazione path con .htaccess
define('BASE_PATH', '');
define('BASE_URL', 'http://localhost');

// Helper function per creare URL puliti
function url($path = '') {
    return BASE_PATH . '/' . ltrim($path, '/');
}

// Helper per asset (CSS, JS, immagini)
function asset($path = '') {
    return BASE_PATH . '/public/assets/' . ltrim($path, '/');
}
