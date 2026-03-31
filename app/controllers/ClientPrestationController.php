<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/auth.php';

requireRole('client');  //  réservé aux clients

class ClientPrestationController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    public function create(): void
    {
        $client_id = $_SESSION['user']['id'];

        $stmt = $this->pdo->prepare(
            "INSERT INTO prestations (titre, description, client_id, statut)
             VALUES (:titre, :desc, :client, 'en_attente')"
        );
        $stmt->execute([
            ':titre'  => trim($_POST['titre']       ?? ''),
            ':desc'   => trim($_POST['description'] ?? ''),
            ':client' => $client_id,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Demande envoyée avec succès.'];
        header('Location: ' . BASE_URL . '/app/views/client/dashboard.php');
        exit;
    }
}

$action = $_GET['action'] ?? '';
$ctrl = new ClientPrestationController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $ctrl->create();
}