<?php
// =============================================================
// config/config.php — Configuration globale
// =============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

define('BASE_URL', 'http://localhost/fgcl');
define('APP_NAME', 'FGCL - Gestion des Prestations');
define('TVA_TAUX', 19.25); // TVA Cameroun

date_default_timezone_set('Africa/Douala');

ini_set('display_errors', 1);
error_reporting(E_ALL);
