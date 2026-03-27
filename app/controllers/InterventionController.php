<?php
// =============================================================
// app/controllers/InterventionController.php
// =============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/auth.php';

requireRole(['admin', 'technicien']);

class InterventionController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /** Toutes les interventions (admin) */
    public function getAll(): array
    {
        return $this->pdo->query(
            "SELECT i.*,
                    CONCAT(c.nom,' ',c.prenom) AS client_nom,
                    CONCAT(t.nom,' ',t.prenom) AS tech_nom,
                    p.titre AS prestation_titre
             FROM interventions i
             JOIN prestations p  ON i.prestation_id = p.id
             JOIN utilisateurs c ON p.client_id     = c.id
             JOIN utilisateurs t ON i.technicien_id  = t.id
             ORDER BY i.date_intervention DESC"
        )->fetchAll();
    }

    /** Interventions d'un technicien donné */
    public function getByTechnicien(int $techId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT i.*,
                    CONCAT(c.nom,' ',c.prenom) AS client_nom,
                    p.titre AS prestation_titre
             FROM interventions i
             JOIN prestations p  ON i.prestation_id = p.id
             JOIN utilisateurs c ON p.client_id     = c.id
             WHERE i.technicien_id = :id
             ORDER BY i.date_intervention DESC"
        );
        $stmt->execute([':id' => $techId]);
        return $stmt->fetchAll();
    }

    /** Créer une intervention (admin) */
    public function create(): void
    {
        $date = ($_POST['date'] ?? '') . ' ' . ($_POST['heure'] ?? '00:00');
        $stmt = $this->pdo->prepare(
            "INSERT INTO interventions (prestation_id, technicien_id, rapport, statut, date_intervention)
             VALUES (:prest, :tech, :rapport, :statut, :date)"
        );
        $stmt->execute([
            ':prest'   => (int)($_POST['prestation_id']  ?? 0),
            ':tech'    => (int)($_POST['technicien_id']  ?? 0),
            ':rapport' => trim($_POST['rapport']          ?? ''),
            ':statut'  => 'planifie',
            ':date'    => $date,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Intervention planifiée avec succès.'];
        header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=interventions');
        exit;
    }

    /** Changer le statut d'une intervention (technicien ou admin) */
    public function updateStatut(): void
    {
        $id     = (int)($_POST['id']     ?? 0);
        $statut = $_POST['statut']       ?? '';
        $rapport = trim($_POST['rapport'] ?? '');

        $allowed = ['planifie', 'en_cours', 'termine'];
        if (!in_array($statut, $allowed, true)) {
            exit('Statut invalide');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE interventions SET statut = :statut, rapport = :rapport WHERE id = :id"
        );
        $stmt->execute([':statut' => $statut, ':rapport' => $rapport, ':id' => $id]);

        // Si terminée, mettre à jour la prestation aussi
        if ($statut === 'termine') {
            $intv = $this->pdo->prepare("SELECT prestation_id FROM interventions WHERE id = :id");
            $intv->execute([':id' => $id]);
            $row = $intv->fetch();
            if ($row) {
                $this->pdo->prepare("UPDATE prestations SET statut='termine' WHERE id=:pid")
                          ->execute([':pid' => $row['prestation_id']]);
            }
        }

        $role = $_SESSION['user']['role'];
        $redirect = ($role === 'admin')
            ? BASE_URL . '/app/views/admin/dashboard.php?section=interventions'
            : BASE_URL . '/app/views/technicien/dashboard.php?section=interventions';

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Statut mis à jour.'];
        header('Location: ' . $redirect);
        exit;
    }
}

$action = $_GET['action'] ?? '';
$ctrl   = new InterventionController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create')        $ctrl->create();
    if ($action === 'updateStatut')  $ctrl->updateStatut();
}
