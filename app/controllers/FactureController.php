<?php
// =============================================================
// app/controllers/FactureController.php
// Génération et gestion des factures
// =============================================================
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/auth.php';

requireRole(['admin', 'client']);

class FactureController
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = getPDO();
    }

    /** Toutes les factures (admin) */
    public function getAll(): array
    {
        return $this->pdo->query(
            "SELECT f.*,
                    CONCAT(u.nom,' ',u.prenom) AS client_nom,
                    p.titre AS prestation_titre
             FROM factures f
             JOIN utilisateurs u ON f.client_id     = u.id
             JOIN prestations  p ON f.prestation_id = p.id
             ORDER BY f.created_at DESC"
        )->fetchAll();
    }

    /** Factures d'un client donné */
    public function getByClient(int $clientId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT f.*, p.titre AS prestation_titre
             FROM factures f
             JOIN prestations p ON f.prestation_id = p.id
             WHERE f.client_id = :id
             ORDER BY f.date_emission DESC"
        );
        $stmt->execute([':id' => $clientId]);
        return $stmt->fetchAll();
    }

    /** Génère une facture pour une prestation terminée */
    public function generer(): void
    {
        $prestationId = (int)($_POST['prestation_id'] ?? 0);

        // Vérifier que la prestation existe et n'a pas déjà de facture
        $check = $this->pdo->prepare(
            "SELECT p.*, CONCAT(u.nom,' ',u.prenom) AS client_nom
             FROM prestations p JOIN utilisateurs u ON p.client_id = u.id
             WHERE p.id = :id"
        );
        $check->execute([':id' => $prestationId]);
        $prestation = $check->fetch();

        if (!$prestation) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Prestation introuvable.'];
            header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=factures');
            exit;
        }

        $alreadyExists = $this->pdo->prepare("SELECT id FROM factures WHERE prestation_id = :id");
        $alreadyExists->execute([':id' => $prestationId]);
        if ($alreadyExists->fetch()) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Une facture existe déjà pour cette prestation.'];
            header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=factures');
            exit;
        }

        // Calcul des montants
        $montantHT  = (float) $prestation['montant'];
        $tva        = TVA_TAUX;
        $montantTTC = $montantHT * (1 + $tva / 100);

        // Numéro de facture automatique : FAC-YYYY-XXXX
        $count = $this->pdo->query("SELECT COUNT(*) FROM factures")->fetchColumn();
        $numero = 'FAC-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO factures (prestation_id, client_id, numero, montant_ht, tva, montant_ttc, statut, date_emission, date_echeance)
             VALUES (:prest, :client, :numero, :ht, :tva, :ttc, 'impayee', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY))"
        );
        $stmt->execute([
            ':prest'  => $prestationId,
            ':client' => $prestation['client_id'],
            ':numero' => $numero,
            ':ht'     => $montantHT,
            ':tva'    => $tva,
            ':ttc'    => $montantTTC,
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => "Facture {$numero} générée avec succès."];
        header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=factures');
        exit;
    }

    /** Télécharger la facture en PDF (HTML imprimable sans librairie externe) */
    public function telecharger(): void
    {
        $id = (int)($_GET['id'] ?? 0);

        $stmt = $this->pdo->prepare(
            "SELECT f.*,
                    CONCAT(u.nom,' ',u.prenom) AS client_nom,
                    u.email AS client_email,
                    u.telephone AS client_tel,
                    u.adresse AS client_adresse,
                    p.titre AS prestation_titre,
                    p.description AS prestation_desc
             FROM factures f
             JOIN utilisateurs u ON f.client_id = u.id
             JOIN prestations  p ON f.prestation_id = p.id
             WHERE f.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $facture = $stmt->fetch();

        if (!$facture) {
            die('Facture introuvable.');
        }

        // Sécurité : un client ne peut voir que ses propres factures
        if ($_SESSION['user']['role'] === 'client' &&
            $facture['client_id'] != $_SESSION['user']['id']) {
            die('Accès refusé.');
        }

        // Générer une page HTML auto-print (simulera le PDF à l'impression)
        require_once __DIR__ . '/../views/admin/facture_pdf.php';
        exit;
    }

    /** Marquer une facture comme payée */
    public function marquerPayee(): void
{
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $this->pdo->prepare(
        "UPDATE factures SET statut = 'payee' WHERE id = :id"
    );
    $stmt->execute([':id' => $id]);
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Facture marquée comme payée.'];
    header('Location: ' . BASE_URL . '/app/views/admin/dashboard.php?section=factures');
    exit;
}
}

$action = $_GET['action'] ?? '';
$ctrl   = new FactureController();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'generer') {
    $ctrl->generer();
} elseif ($action === 'telecharger') {
    $ctrl->telecharger();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'marquerPayee') { // ← ajouter
    $ctrl->marquerPayee();
}

