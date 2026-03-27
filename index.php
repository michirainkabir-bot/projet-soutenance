<?php
// =============================================================
// index.php — Point d'entrée : redirige si déjà connecté
// =============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/helpers/auth.php';

// Si déjà connecté, aller au bon dashboard
if (!empty($_SESSION['user'])) {
    redirectToDashboard();
}

// Sinon afficher la page de connexion
require_once __DIR__ . '/app/views/auth/login.php';
