<?php
// =============================================================
// app/controllers/DemandeController.php
// =============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/auth.php';

requireRole('client');

class DemandeController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function envoyer(): void
    {
        $client_id = $_SESSION['user']['id'];

        $stmt = $this->pdo->prepare(
            "INSERT INTO prestations (titre, description, client_id, statut, date_debut)
             VALUES (:titre, :desc, :client, 'en_attente', :date_debut)"
        );
        $stmt->execute([
            ':titre'      => trim($_POST['prestation_titre'] ?? ''),
            ':desc'       => trim($_POST['description']      ?? ''),
            ':client'     => $client_id,
            ':date_debut' => !empty($_POST['date']) ? $_POST['date'] : null,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Demande envoyée avec succès.'];
        header('Location: ' . BASE_URL . '/app/views/client/dashboard.php?success=1');
        exit;
    }
}

$action = $_GET['action'] ?? '';
$ctrl = new DemandeController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'envoyer') {
    $ctrl->envoyer();
}