<?php
// =============================================================
// app/helpers/auth.php — Protection des routes et sessions
// =============================================================

/**
 * Vérifie que l'utilisateur est connecté.
 * Sinon redirige vers la page de connexion.
 */
function requireLogin(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Vérifie que l'utilisateur a le rôle requis.
 * @param string|array $roles  Rôle(s) autorisé(s)
 */
function requireRole($roles): void
{
    requireLogin();
    $roles = (array) $roles;
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        // Accès interdit → redirection vers son propre dashboard
        redirectToDashboard();
    }
}

/**
 * Redirige vers le dashboard selon le rôle connecté.
 */
function redirectToDashboard(): void
{
    $role = $_SESSION['user']['role'] ?? '';
    switch ($role) {
        case 'admin':
            header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php');
            break;
        case 'technicien':
            header('Location: ' . BASE_URL . '/app/views/technicien/dashboard.php');
            break;
        case 'client':
            header('Location: ' . BASE_URL . '/app/views/client/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/index.php');
    }
    exit;
}

/**
 * Retourne l'utilisateur connecté ou null.
 */
function getUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Échappe une valeur pour l'affichage HTML sécurisé.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
