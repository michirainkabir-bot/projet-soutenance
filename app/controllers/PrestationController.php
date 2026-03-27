<?php
// =============================================================
// app/controllers/PrestationController.php
// =============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/auth.php';

requireRole('admin');

class PrestationController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function getAll(): array
    {
        return $this->pdo->query(
            "SELECT p.*, 
                    CONCAT(c.nom,' ',c.prenom) AS client_nom,
                    CONCAT(t.nom,' ',t.prenom) AS tech_nom
             FROM prestations p
             LEFT JOIN utilisateurs c ON p.client_id = c.id
             LEFT JOIN utilisateurs t ON p.technicien_id = t.id
             ORDER BY p.created_at DESC"
        )->fetchAll();
    }

    public function create(): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO prestations (titre, description, client_id, technicien_id, statut, date_debut, date_fin, montant)
             VALUES (:titre, :desc, :client, :tech, :statut, :debut, :fin, :montant)"
        );
        $stmt->execute([
            ':titre'   => trim($_POST['titre']         ?? ''),
            ':desc'    => trim($_POST['description']   ?? ''),
            ':client'  => (int) ($_POST['client_id']   ?? 0),
            ':tech'    => ($_POST['technicien_id'] !== '') ? (int)$_POST['technicien_id'] : null,
            ':statut'  => $_POST['statut']              ?? 'en_attente',
            ':debut'   => $_POST['date_debut']          ?? null,
            ':fin'     => $_POST['date_fin']            ?? null,
            ':montant' => (float) ($_POST['montant']   ?? 0),
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Prestation créée avec succès.'];
        header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=prestations');
        exit;
    }

    public function delete(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $this->pdo->prepare("DELETE FROM prestations WHERE id = :id")->execute([':id' => $id]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Prestation supprimée.'];
        header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=prestations');
        exit;
    }

    public function update(): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE prestations SET titre=:titre, description=:desc, client_id=:client,
             technicien_id=:tech, statut=:statut, date_debut=:debut, date_fin=:fin,
             montant=:montant WHERE id=:id"
        );
        $stmt->execute([
            ':titre'   => trim($_POST['titre']         ?? ''),
            ':desc'    => trim($_POST['description']   ?? ''),
            ':client'  => (int) ($_POST['client_id']   ?? 0),
            ':tech'    => ($_POST['technicien_id'] !== '') ? (int)$_POST['technicien_id'] : null,
            ':statut'  => $_POST['statut']              ?? 'en_attente',
            ':debut'   => $_POST['date_debut']          ?? null,
            ':fin'     => $_POST['date_fin']            ?? null,
            ':montant' => (float) ($_POST['montant']   ?? 0),
            ':id'      => (int) ($_POST['id']           ?? 0),
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Prestation mise à jour.'];
        header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=prestations');
        exit;
    }
}

$action = $_GET['action'] ?? '';
$ctrl   = new PrestationController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') $ctrl->create();
    if ($action === 'update') $ctrl->update();
} elseif ($action === 'delete') {
    $ctrl->delete();
}
