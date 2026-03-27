<?php
// =============================================================
// app/controllers/AuthController.php
// Gestion de la connexion et déconnexion
// =============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/auth.php';

class AuthController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /**
     * Traite le formulaire de connexion (POST)
     */
    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $error    = '';

        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } else {
            // Recherche de l'utilisateur par email (actif uniquement)
            $stmt = $this->pdo->prepare(
                "SELECT * FROM utilisateurs WHERE email = :email AND actif = 1 LIMIT 1"
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Connexion réussie — on stocke les infos en session
                $_SESSION['user'] = [
                    'id'     => $user['id'],
                    'nom'    => $user['nom'],
                    'prenom' => $user['prenom'],
                    'email'  => $user['email'],
                    'role'   => $user['role'],
                ];
                redirectToDashboard();
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        }

        // En cas d'erreur, réafficher le login avec le message
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Déconnexion
     */
    public function logout(): void
    {
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// ---- Routage simple ----
$action = $_GET['action'] ?? '';
$ctrl = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $ctrl->login();
} elseif ($action === 'logout') {
    $ctrl->logout();
}
