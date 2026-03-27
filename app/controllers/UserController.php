<?php
// =============================================================
// app/controllers/UserController.php
// CRUD utilisateurs (clients + techniciens)
// =============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/auth.php';

requireRole('admin');

class UserController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /** Liste tous les utilisateurs d'un rôle donné */
    public function getByRole(string $role): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM utilisateurs WHERE role = :role ORDER BY nom ASC"
        );
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll();
    }

    /** Crée un utilisateur */
    public function create(): void
    {
        $nom      = trim($_POST['nom']      ?? '');
        $prenom   = trim($_POST['prenom']   ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $role     = $_POST['role']          ?? 'client';
        $tel      = trim($_POST['telephone'] ?? '');
        $adresse  = trim($_POST['adresse']  ?? '');

        if (empty($nom) || empty($email) || empty($password)) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Champs obligatoires manquants.'];
            $this->redirect($role);
        }

        // Vérification email unique
        $check = $this->pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email");
        $check->execute([':email' => $email]);
        if ($check->fetch()) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Cet email est déjà utilisé.'];
            $this->redirect($role);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone, adresse)
             VALUES (:nom, :prenom, :email, :mdp, :role, :tel, :adresse)"
        );
        $stmt->execute([
            ':nom'     => $nom,
            ':prenom'  => $prenom,
            ':email'   => $email,
            ':mdp'     => $hash,
            ':role'    => $role,
            ':tel'     => $tel,
            ':adresse' => $adresse,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => ucfirst($role) . ' ajouté avec succès.'];
        $this->redirect($role);
    }

    /** Supprime un utilisateur */
    public function delete(): void
    {
        $id   = (int) ($_GET['id']   ?? 0);
        $role = $_GET['role'] ?? 'client';

        $stmt = $this->pdo->prepare("UPDATE utilisateurs SET actif = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Utilisateur supprimé.'];
        $this->redirect($role);
    }

    /** Retourne un utilisateur par son id */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Met à jour un utilisateur */
    public function update(): void
    {
        $id      = (int) ($_POST['id']       ?? 0);
        $nom     = trim($_POST['nom']         ?? '');
        $prenom  = trim($_POST['prenom']      ?? '');
        $email   = trim($_POST['email']       ?? '');
        $tel     = trim($_POST['telephone']   ?? '');
        $adresse = trim($_POST['adresse']     ?? '');
        $role    = $_POST['role']             ?? 'client';

        $params = [
            ':nom'     => $nom,
            ':prenom'  => $prenom,
            ':email'   => $email,
            ':tel'     => $tel,
            ':adresse' => $adresse,
            ':id'      => $id,
        ];

        // Mise à jour du mot de passe seulement si fourni
        $passwordSQL = '';
        if (!empty($_POST['password'])) {
            $passwordSQL = ', mot_de_passe = :mdp';
            $params[':mdp'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $stmt = $this->pdo->prepare(
            "UPDATE utilisateurs SET nom=:nom, prenom=:prenom, email=:email,
             telephone=:tel, adresse=:adresse {$passwordSQL} WHERE id=:id"
        );
        $stmt->execute($params);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Utilisateur mis à jour.'];
        $this->redirect($role);
    }

    private function redirect(string $role): void
    {
        $section = ($role === 'technicien') ? 'techniciens' : 'clients';
        header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=' . $section);
        exit;
    }
}

// ---- Routage ----
$action = $_GET['action'] ?? '';
$ctrl   = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') $ctrl->create();
    if ($action === 'update') $ctrl->update();
} elseif ($action === 'delete') {
    $ctrl->delete();
}
