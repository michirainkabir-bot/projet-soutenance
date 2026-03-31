<?php
// =============================================================
// app/views/admin/dashboard.php
// Dashboard administrateur — converti depuis "dashboard admin.html"
// =============================================================
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireRole('admin');

$pdo  = getPDO();
$user = getUser();

// ── Section active (depuis URL ou défaut)
$section = $_GET['section'] ?? 'dashboard';

// ── Récupération des données selon la section active
// Statistiques globales (toujours chargées pour les cards)
$nbClients      = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='client' AND actif=1")->fetchColumn();
$nbTechniciens  = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role='technicien' AND actif=1")->fetchColumn();
$nbInterventions= $pdo->query("SELECT COUNT(*) FROM interventions")->fetchColumn();
$revenus        = $pdo->query("SELECT COALESCE(SUM(montant_ttc),0) FROM factures WHERE statut='payee'")->fetchColumn();

// Clients
$clients = $pdo->query(
    "SELECT * FROM utilisateurs WHERE role='client' AND actif=1 ORDER BY nom ASC"
)->fetchAll();

// Techniciens
$techniciens = $pdo->query(
    "SELECT * FROM utilisateurs WHERE role='technicien' AND actif=1 ORDER BY nom ASC"
)->fetchAll();

// Prestations avec noms
$prestations = $pdo->query(
    "SELECT p.*, CONCAT(c.nom,' ',c.prenom) AS client_nom,
            CONCAT(t.nom,' ',t.prenom) AS tech_nom
     FROM prestations p
     LEFT JOIN utilisateurs c ON p.client_id=c.id
     LEFT JOIN utilisateurs t ON p.technicien_id=t.id
     ORDER BY p.created_at DESC"
)->fetchAll();

// Interventions avec détails
$interventions = $pdo->query(
    "SELECT i.*, CONCAT(c.nom,' ',c.prenom) AS client_nom,
            CONCAT(t.nom,' ',t.prenom) AS tech_nom,
            p.titre AS prestation_titre
     FROM interventions i
     JOIN prestations p  ON i.prestation_id=p.id
     JOIN utilisateurs c ON p.client_id=c.id
     JOIN utilisateurs t ON i.technicien_id=t.id
     ORDER BY i.date_intervention DESC"
)->fetchAll();

// Stats interventions
$intv_total   = $pdo->query("SELECT COUNT(*) FROM interventions")->fetchColumn();
$intv_attente = $pdo->query("SELECT COUNT(*) FROM interventions WHERE statut='planifie'")->fetchColumn();
$intv_cours   = $pdo->query("SELECT COUNT(*) FROM interventions WHERE statut='en_cours'")->fetchColumn();
$intv_termine = $pdo->query("SELECT COUNT(*) FROM interventions WHERE statut='termine'")->fetchColumn();

// Factures
$factures = $pdo->query(
    "SELECT f.*, CONCAT(u.nom,' ',u.prenom) AS client_nom, p.titre AS prestation_titre
     FROM factures f
     JOIN utilisateurs u ON f.client_id=u.id
     JOIN prestations  p ON f.prestation_id=p.id
     ORDER BY f.created_at DESC"
)->fetchAll();

// Prestations — uniquement les demandes des clients (pas les prestations catalogue)
$demandesClients = $pdo->query(
    "SELECT p.*, CONCAT(c.nom,' ',c.prenom) AS client_nom,
            CONCAT(t.nom,' ',t.prenom) AS tech_nom
     FROM prestations p
     INNER JOIN utilisateurs c ON p.client_id = c.id
     LEFT JOIN utilisateurs t ON p.technicien_id = t.id
     WHERE c.role = 'client'
     ORDER BY p.created_at DESC"
)->fetchAll();

// Données graphique (interventions par mois)
$chartMois = $pdo->query(
    "SELECT DATE_FORMAT(date_intervention,'%b') AS mois,
            COUNT(*) AS total
     FROM interventions
     WHERE YEAR(date_intervention)=YEAR(CURDATE())
     GROUP BY MONTH(date_intervention)
     ORDER BY MONTH(date_intervention)"
)->fetchAll();
$chartLabels = json_encode(array_column($chartMois, 'mois'));
$chartData   = json_encode(array_column($chartMois, 'total'));

// Flash message
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Fonction statut label
function statutLabel(string $s): string {
    return match($s) {
        'en_attente' => '<span class="attente">En attente</span>',
        'en_cours'   => '<span class="en-cours">En cours</span>',
        'termine'    => '<span class="termine">Terminée</span>',
        'annule'     => '<span style="color:#999">Annulée</span>',
        'planifie'   => '<span class="attente">Planifiée</span>',
        'impayee'    => '<span class="attente">Impayée</span>',
        'payee'      => '<span class="termine">Payée</span>',
        'annulee'    => '<span style="color:#999">Annulée</span>',
        default      => htmlspecialchars($s),
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin — <?= APP_NAME ?></title>
</head>
<body>

<!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
<div class="sidebar">
    <div class="logo">
        <img src="<?= BASE_URL ?>/public/images/logo.png" alt="logo" width="170px" height="150px">
    </div>
    <h2>Admin Panel</h2>
    <ul>
        <li onclick="showSection('dashboard')"><i class="fa-solid fa-chart-line"></i> Dashboard</li>
        <li onclick="showSection('clients')"><i class="fa-solid fa-users"></i> Clients</li>
        <li onclick="showSection('techniciens')"><i class="fa-solid fa-user-gear"></i> Techniciens</li>
        <li onclick="showSection('interventions')"><i class="fa-solid fa-screwdriver-wrench"></i> Interventions</li>
        <li onclick="showSection('prestations')"><i class="fa-solid fa-briefcase"></i> Prestations</li>
        <li onclick="showSection('notifications')"><i class="fa-solid fa-bell"></i> Notifications</li>
        <li onclick="showSection('factures')"><i class="fa-solid fa-file-invoice"></i> Factures</li>
        <li onclick="window.location.href='<?= BASE_URL ?>/app/controllers/AuthController.php?action=logout'">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </li>
    </ul>
</div>

<!-- ═══════════════════════ CONTENU ═══════════════════════ -->
<div class="main">

    <header>
        <div class="topbar">
            <h1>Tableau de bord administrateur</h1>
            <div>
                <i class="fa-solid fa-bell" onclick="showSection('notifications')"></i>
                <i class="fa-solid fa-user"></i>
                <?= e($user['prenom'] . ' ' . $user['nom']) ?>
            </div>
        </div>
    </header>

    <!-- Flash message -->
    <?php if ($flash): ?>
    <div style="margin:10px 20px;padding:12px 18px;border-radius:8px;
        background:<?= $flash['type']==='success' ? '#d4edda' : '#f8d7da' ?>;
        color:<?= $flash['type']==='success' ? '#155724' : '#721c24' ?>;font-size:14px;">
        <?= e($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- ════════════════ DASHBOARD ════════════════ -->
    <div id="dashboard" class="section active">
        <div class="cards">
            <div class="card">
                <div><h3><?= $nbClients ?></h3><p>Clients</p></div>
                <div class="iconBox blue"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="card">
                <div><h3><?= $nbTechniciens ?></h3><p>Techniciens</p></div>
                <div class="iconBox green"><i class="fa-solid fa-user-gear"></i></div>
            </div>
            <div class="card">
                <div><h3><?= $nbInterventions ?></h3><p>Interventions</p></div>
                <div class="iconBox orange"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            </div>
            <div class="card">
                <div><h3><?= number_format($revenus, 0, ',', ' ') ?> FCFA</h3><p>Revenus</p></div>
                <div class="iconBox purple"><i class="fa-solid fa-coins"></i></div>
            </div>
        </div>

        <section class="content active">
            <div class="charts-container">
                <div class="chart-card">
                    <h3>Interventions par mois</h3>
                    <canvas id="interventionsChart"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Statut des interventions</h3>
                    <canvas id="statutChart"></canvas>
                </div>
            </div>
        </section>
    </div>

    <!-- ════════════════ CLIENTS ════════════════ -->
    <div id="clients" class="section">
        <h2>Gestion Clients</h2>
        <div class="cl">
            <input type="text" id="searchClient" placeholder="Rechercher client" oninput="filterTable('tableClients', this.value)">
            <button class="btn-add-client" onclick="openModalclient()">
                <i class="fa-solid fa-user-plus"></i> Ajouter un client
            </button>
        </div>
        <section class="table-container">
            <table id="tableClients">
                <thead>
                    <tr>
                        <th>Id</th><th>Nom</th><th>Prénom</th><th>Email</th>
                        <th>Téléphone</th><th>Adresse</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($clients as $c): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= e($c['nom']) ?></td>
                    <td><?= e($c['prenom']) ?></td>
                    <td><?= e($c['email']) ?></td>
                    <td><?= e($c['telephone'] ?? '—') ?></td>
                    <td><?= e($c['adresse'] ?? '—') ?></td>
                    <td class="actions">
                        <i class="fa-solid fa-pen" title="Modifier"
                           onclick="openEditUserModal(<?= htmlspecialchars(json_encode($c)) ?>)"></i>
                        <a href="<?= BASE_URL ?>/app/controllers/UserController.php?action=delete&id=<?= $c['id'] ?>&role=client"
                           onclick="return confirm('Supprimer ce client ?')">
                            <i class="fa-solid fa-trash" style="color:#e74c3c"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($clients)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#999">Aucun client enregistré</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- ════════════════ TECHNICIENS ════════════════ -->
    <div id="techniciens" class="section">
        <h2>Gestion Techniciens</h2>
        <div class="cl">
            <input type="text" placeholder="Rechercher technicien" oninput="filterTable('tableTech', this.value)">
            <button class="btn-add-tech" onclick="openTechModal()">
                <i class="fa-solid fa-user-gear"></i> Ajouter un technicien
            </button>
        </div>
        <section class="table-container">
            <table id="tableTech">
                <thead>
                    <tr>
                        <th>Id</th><th>Nom</th><th>Prénom</th><th>Email</th>
                        <th>Téléphone</th><th>Spécialité</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($techniciens as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><?= e($t['nom']) ?></td>
                    <td><?= e($t['prenom']) ?></td>
                    <td><?= e($t['email']) ?></td>
                    <td><?= e($t['telephone'] ?? '—') ?></td>
                    <td><?= e($t['specialite'] ?? '—') ?></td>
                    <td class="actions">
                        <i class="fa-solid fa-pen" title="Modifier"
                           onclick="openEditUserModal(<?= htmlspecialchars(json_encode($t)) ?>)"></i>
                        <a href="<?= BASE_URL ?>/app/controllers/UserController.php?action=delete&id=<?= $t['id'] ?>&role=technicien"
                           onclick="return confirm('Supprimer ce technicien ?')">
                            <i class="fa-solid fa-trash" style="color:#e74c3c"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($techniciens)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#999">Aucun technicien enregistré</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- ════════════════ INTERVENTIONS ════════════════ -->
    <div id="interventions" class="section">
        <h1>Gestion des Interventions</h1>
        <div class="intervention-stats">
            <div class="card"><i class="fa-solid fa-screwdriver-wrench"></i><h3><?= $intv_total ?></h3><p>Total</p></div>
            <div class="card"><i class="fa-solid fa-clock" style="color:red"></i><h3><?= $intv_attente ?></h3><p>Planifiées</p></div>
            <div class="card"><i class="fa-solid fa-spinner" style="color:orange"></i><h3><?= $intv_cours ?></h3><p>En cours</p></div>
            <div class="card"><i class="fa-solid fa-circle-check" style="color:green"></i><h3><?= $intv_termine ?></h3><p>Terminées</p></div>
        </div>

        <button class="btn-add" onclick="openModalIntervention()">
            <i class="fa-solid fa-plus"></i> Planifier une intervention
        </button>

        <section class="table-container">
            <table class="intervention-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Client</th><th>Technicien</th><th>Prestation</th>
                        <th>Date</th><th>Statut</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($interventions as $i): ?>
                <tr>
                    <td><?= $i['id'] ?></td>
                    <td><?= e($i['client_nom']) ?></td>
                    <td><?= e($i['tech_nom']) ?></td>
                    <td><?= e($i['prestation_titre']) ?></td>
                    <td><?= date('d M Y H:i', strtotime($i['date_intervention'])) ?></td>
                    <td><?= statutLabel($i['statut']) ?></td>
                    <td>
                        <button onclick="openStatutModal(<?= $i['id'] ?>, '<?= $i['statut'] ?>', '<?= e($i['rapport'] ?? '') ?>')">
                            Modifier
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($interventions)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#999">Aucune intervention</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- ════════════════ PRESTATIONS ════════════════ -->
    <div id="prestations" class="section">
        <button onclick="openPrestationModal()">
            <i class="fa-solid fa-plus"></i> Ajouter une prestation
        </button>
        <section class="table-container">
            <section class="table-section">
                <h2>Prestations</h2>
                <table id="listePrestations">
                    <thead>
                        <tr>
                            <th>ID</th><th>Titre</th><th>Client</th>
                            <th>Montant</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($demandesClients as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= e($p['titre']) ?></td>
                        <td><?= e($p['client_nom']) ?></td>
                        <td><?= number_format($p['montant'], 0, ',', ' ') ?> FCFA</td>
                        <td class="actions">
                            <i class="fa-solid fa-pen" onclick="openEditPrestModal(<?= htmlspecialchars(json_encode($p)) ?>)"></i>
                            <a href="<?= BASE_URL ?>/app/controllers/PrestationController.php?action=delete&id=<?= $p['id'] ?>"
                               onclick="return confirm('Supprimer cette prestation ?')">
                                <i class="fa-solid fa-trash" style="color:#e74c3c"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($prestations)): ?>
                        <tr><td colspan="7" style="text-align:center;color:#999">Aucune prestation</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </section>
    </div>

    <!-- ════════════════ NOTIFICATIONS ════════════════ -->
    <div id="notifications" class="section">
        <h2>Notifications</h2>
        <?php
        $notifs = $pdo->query(
            "SELECT i.id, CONCAT(c.nom,' ',c.prenom) AS client_nom, p.titre, i.statut, i.date_intervention
             FROM interventions i
             JOIN prestations p ON i.prestation_id=p.id
             JOIN utilisateurs c ON p.client_id=c.id
             ORDER BY i.created_at DESC LIMIT 10"
        )->fetchAll();
        foreach ($notifs as $n):
            $icon = $n['statut'] === 'termine' ? 'fa-circle-check' : 'fa-bell';
            $msg  = $n['statut'] === 'termine'
                ? "Intervention #{$n['id']} terminée — {$n['titre']} ({$n['client_nom']})"
                : "Intervention #{$n['id']} planifiée — {$n['titre']} ({$n['client_nom']})";
        ?>
        <p><i class="fa-solid <?= $icon ?>"></i> <?= e($msg) ?></p>
        <?php endforeach; ?>
        <?php if (empty($notifs)): ?><p style="color:#999">Aucune notification</p><?php endif; ?>
    </div>

    <!-- ════════════════ FACTURES ════════════════ -->
    <div id="factures" class="section">
        <h1>Gestion des Factures</h1>

        <?php if (!empty($prestSansFact)): ?>
        <div style="background:#fff3cd;border:1px solid #ffc107;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
            <strong>Prestations terminées sans facture :</strong>
            <form action="<?= BASE_URL ?>/app/controllers/FactureController.php?action=generer" method="POST"
                  style="display:inline-flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:8px;">
                <select name="prestation_id" required style="padding:6px 10px;border-radius:6px;border:1px solid #ccc;">
                    <?php foreach ($prestSansFact as $ps): ?>
                    <option value="<?= $ps['id'] ?>"><?= e($ps['titre']) ?> — <?= e($ps['client_nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-add">
                    <i class="fa-solid fa-file-invoice"></i> Générer la facture
                </button>
            </form>
        </div>
        <?php endif; ?>

        <section class="table-container">
            <table class="content">
                <thead>
                    <tr>
                        <th>N° Facture</th><th>Client</th><th>Prestation</th><th>Date</th>
                        <th>Montant HT</th><th>TVA</th><th>TTC</th><th>Statut</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($factures as $f): ?>
                <tr>
                    <td><?= e($f['numero']) ?></td>
                    <td><?= e($f['client_nom']) ?></td>
                    <td><?= e($f['prestation_titre']) ?></td>
                    <td><?= date('d/m/Y', strtotime($f['date_emission'])) ?></td>
                    <td><?= number_format($f['montant_ht'], 0, ',', ' ') ?> FCFA</td>
                    <td><?= $f['tva'] ?>%</td>
                    <td><?= number_format($f['montant_ttc'], 0, ',', ' ') ?> FCFA</td>
                    <td><?= statutLabel($f['statut']) ?></td>
                    <td>
                        <?php if ($f['statut'] === 'impayee'): ?>
                            <form action="<?= BASE_URL ?>/app/controllers/FactureController.php?action=marquerPayee" method="POST" style="display:inline">
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <button type="submit" style="background:#27ae60;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer">
                                        <i class="fa-solid fa-check"></i> Marquer payée
                                    </button>
                            </form>
                        <?php endif; ?>
                         <a href="<?= BASE_URL ?>/app/controllers/FactureController.php?action=telecharger&id=<?= $f['id'] ?>"
                                 target="_blank" title="Télécharger PDF">
                                <i class="fa-solid fa-file-pdf action" style="color:#e74c3c;font-size:18px;cursor:pointer"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($factures)): ?>
                    <tr><td colspan="9" style="text-align:center;color:#999">Aucune facture générée</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

</div><!-- /.main -->


<!-- ═══════════════ MODAL : Ajouter client ═══════════════ -->
<div id="clientModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModalclient()">&times;</span>
        <h2>Ajouter un client</h2>
        <form id="clientForm" action="<?= BASE_URL ?>/app/controllers/UserController.php?action=create" method="POST">
            <input type="hidden" name="role" value="client">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" placeholder="Nom" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" placeholder="Prénom" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" placeholder="Téléphone">
            </div>
            <div class="form-group">
                <label>Adresse</label>
                <input type="text" name="adresse" placeholder="Adresse">
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="Mot de passe" required>
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
                <button type="button" class="btn-cancel" onclick="closeModalclient()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════ MODAL : Ajouter technicien ═══════════════ -->
<div id="techModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeTechModal()">&times;</span>
        <h2>Ajouter un technicien</h2>
        <form id="techForm" action="<?= BASE_URL ?>/app/controllers/UserController.php?action=create" method="POST">
            <input type="hidden" name="role" value="technicien">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" placeholder="Nom" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" placeholder="Prénom" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" placeholder="Téléphone">
            </div>
            <div class="form-group">
                <label>Spécialité</label>
                    <select name="specialite">
                        <option value="">-- Choisir --</option>
                        <option value="Réseau">Réseau</option>
                        <option value="Maintenance IT">Maintenance IT</option>
                        <option value="Développement">Développement</option>
                        <option value="Sécurité informatique">Sécurité informatique</option>
                        <option value="Support technique">Support technique</option>
                    </select>
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="Mot de passe" required>
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
                <button type="button" class="btn-cancel" onclick="closeTechModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════ MODAL : Modifier utilisateur ═══════════════ -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editUserModal').style.display='none'">&times;</span>
        <h2>Modifier l'utilisateur</h2>
        <form action="<?= BASE_URL ?>/app/controllers/UserController.php?action=update" method="POST">
            <input type="hidden" name="id"   id="editUserId">
            <input type="hidden" name="role" id="editUserRole">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" id="editNom" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" id="editPrenom" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="editEmail" required>
            </div>
            <div class="form-group">
                <label>Téléphone</label>
                <input type="text" name="telephone" id="editTel">
            </div>
            <div class="form-group">
                <label>Spécialité</label>
                    <select name="specialite">
                        <option value="">-- Choisir --</option>
                        <option value="Réseau">Réseau</option>
                        <option value="Maintenance IT">Maintenance IT</option>
                        <option value="Développement">Développement</option>
                        <option value="Sécurité informatique">Sécurité informatique</option>
                        <option value="Support technique">Support technique</option>
                    </select>
            </div>
            <div class="form-group">
                <label>Adresse</label>
                <input type="text" name="adresse" id="editAdresse">
            </div>
            <div class="form-group">
                <label>Nouveau mot de passe <small>(laisser vide pour ne pas changer)</small></label>
                <input type="password" name="password">
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn-save">Enregistrer</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('editUserModal').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════ MODAL : Planifier intervention ═══════════════ -->
<div id="modalIntervention" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModalIntervention()">&times;</span>
        <h2>Planifier une intervention</h2>
        <form action="<?= BASE_URL ?>/app/controllers/InterventionController.php?action=create" method="POST">
            <label>Prestation</label>
                <select name="prestation_id" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($prestations as $p): ?>
                    <option value="<?= $p['id'] ?>">
                    <?= e($p['titre']) ?> — <?= e($p['client_nom']) ?>
                    <?php if (!empty($p['date_debut'])): ?>
                       (Souhaité le : <?= date('d/m/Y', strtotime($p['date_debut'])) ?>)
                    <?php endif; ?>
                </option>
                    <?php endforeach; ?>
                </select>

            <label>Technicien</label>
            <select name="technicien_id" required>
                <option value="">-- Choisir --</option>
                <?php foreach ($techniciens as $t): ?>
                <option value="<?= $t['id'] ?>"><?= e($t['prenom'].' '.$t['nom']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Date</label>
                <input type="date" name="date" id="dateIntervention" required>

            <label>Heure</label>
                <input type="time" name="heure" id="heureIntervention" required>
            <label>Rapport / Description</label>
            <textarea name="rapport" placeholder="Détails de l'intervention..."></textarea>

            <button type="submit">Valider</button>
        </form>
    </div>
</div>

<!-- ═══════════════ MODAL : Modifier statut intervention ═══════════════ -->
<div id="modalStatut" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modalStatut').style.display='none'">&times;</span>
        <h2>Modifier le statut</h2>
        <form action="<?= BASE_URL ?>/app/controllers/InterventionController.php?action=updateStatut" method="POST">
            <input type="hidden" name="id" id="statutId">
            <label>Statut</label>
            <select name="statut" id="statutSelect">
                <option value="planifie">Planifiée</option>
                <option value="en_cours">En cours</option>
                <option value="termine">Terminée</option>
            </select>
            <label>Rapport</label>
            <textarea name="rapport" id="statutRapport" placeholder="Rapport d'intervention..."></textarea>
            <button type="submit">Enregistrer</button>
        </form>
    </div>
</div>

<!-- ═══════════════ MODAL : Ajouter prestation ═══════════════ -->
<div id="prestationModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('prestationModal').style.display='none'">&times;</span>
        <h2>Ajouter une prestation</h2>
        <form action="<?= BASE_URL ?>/app/controllers/PrestationController.php?action=create" method="POST">
            <div class="form-group">
                <label>Titre</label>
                <input type="text" name="titre" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"></textarea>
            </div>
            <div class="form-group">
                <label>Client</label>
                <select name="client_id" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['prenom'].' '.$c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Montant (FCFA)</label>
                <input type="number" name="montant" min="0" step="100" required>
            </div>
            <div class="form-group">
                <label>Date début</label>
                <input type="date" name="date_debut">
            </div>
            <div class="form-group">
                <label>Date fin</label>
                <input type="date" name="date_fin">
            </div>
            <div class="form-buttons">
                <button type="submit" class="btn-save">Enregistrer</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('prestationModal').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════ MODAL : Modifier prestation ═══════════════ -->
<div id="editPrestModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editPrestModal').style.display='none'">&times;</span>
        <h2>Modifier la prestation</h2>
        <form action="<?= BASE_URL ?>/app/controllers/PrestationController.php?action=update" method="POST">
            <input type="hidden" name="id" id="editPrestId">
            <div class="form-group">
                <label>Titre</label>
                <input type="text" name="titre" id="editPrestTitre" required>
            </div>
            <div class="form-group">
                <label>Montant (FCFA)</label>
                <input type="number" name="montant" id="editPrestMontant" min="0" step="100">
            </div>
            <div class="form-group">
                <label>Technicien</label>
                <select name="technicien_id" id="editPrestTech">
                    <option value="">-- Non assigné --</option>
                    <?php foreach ($techniciens as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= e($t['prenom'].' '.$t['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Statut</label>
                <select name="statut" id="editPrestStatut">
                    <option value="en_attente">En attente</option>
                    <option value="en_cours">En cours</option>
                    <option value="termine">Terminée</option>
                    <option value="annule">Annulée</option>
                </select>
            </div>
            <!-- Champs cachés requis par le contrôleur -->
            <input type="hidden" name="client_id"   id="editPrestClient">
            <input type="hidden" name="description" id="editPrestDesc">
            <input type="hidden" name="date_debut"  id="editPrestDebut">
            <input type="hidden" name="date_fin"    id="editPrestFin">
            <div class="form-buttons">
                <button type="submit" class="btn-save">Enregistrer</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('editPrestModal').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════ SCRIPTS ═══════════════ -->
<script src="<?= BASE_URL ?>/public/js/admin.js"></script>
<script>
// ── Section active au chargement (depuis URL)
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const section   = urlParams.get('section') || 'dashboard';
    showSection(section);
});

// ── Graphiques dynamiques (données PHP injectées)
  function initCharts() {
    // Éviter de recréer les charts si déjà initialisés
    if (window.chart1) return;

    const ctx1 = document.getElementById('interventionsChart').getContext('2d');
    window.chart1 = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?= $chartLabels ?: '["Jan","Fév","Mar","Avr","Mai","Jun"]' ?>,
            datasets: [{
                label: "Interventions",
                data: <?= $chartData ?: '[0,0,0,0,0,0]' ?>,
                backgroundColor: 'rgba(0,123,255,0.2)',
                borderColor: 'rgba(0,123,255,1)',
                borderWidth: 2, tension: 0.4, fill: true
            }]
        },
        options: { responsive: true, plugins: { legend: { display: true } },
                   scales: { y: { beginAtZero: true } } }
    });

    const ctx2 = document.getElementById('statutChart').getContext('2d');
    window.chart2 = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['Planifiées', 'En cours', 'Terminées'],
            datasets: [{
                data: [<?= (int)$intv_attente ?>, <?= (int)$intv_cours ?>, <?= (int)$intv_termine ?>],
                backgroundColor: ['#e74c3c', '#f39c12', '#27ae60']
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } },
                   scales: { y: { beginAtZero: true } } }
    });
}

// ── Modals clients / techniciens
function openModalclient()      { document.getElementById('clientModal').style.display = 'flex'; }
function closeModalclient()     { document.getElementById('clientModal').style.display = 'none'; }
function openTechModal()  { document.getElementById('techModal').style.display = 'flex'; }
function closeTechModal() { document.getElementById('techModal').style.display = 'none'; }

// ── Modal modifier utilisateur
function openEditUserModal(user) {
    document.getElementById('editUserId').value   = user.id;
    document.getElementById('editUserRole').value = user.role;
    document.getElementById('editNom').value      = user.nom;
    document.getElementById('editPrenom').value   = user.prenom;
    document.getElementById('editEmail').value    = user.email;
    document.getElementById('editTel').value      = user.telephone || '';
    document.getElementById('editAdresse').value  = user.adresse || '';
    document.getElementById('editUserModal').style.display = 'flex';
}

// ── Modal interventions
function openModalIntervention()  { document.getElementById('modalIntervention').style.display = 'flex'; }
function closeModalIntervention() { document.getElementById('modalIntervention').style.display = 'none'; }

function openStatutModal(id, statut, rapport) {
    document.getElementById('statutId').value      = id;
    document.getElementById('statutSelect').value  = statut;
    document.getElementById('statutRapport').value = rapport;
    document.getElementById('modalStatut').style.display = 'flex';
}

// ── Modal prestations
function openPrestationModal() { document.getElementById('prestationModal').style.display = 'flex'; }

function openEditPrestModal(p) {
    document.getElementById('editPrestId').value      = p.id;
    document.getElementById('editPrestTitre').value   = p.titre;
    document.getElementById('editPrestMontant').value = p.montant;
    document.getElementById('editPrestTech').value    = p.technicien_id || '';
    document.getElementById('editPrestStatut').value  = p.statut;
    document.getElementById('editPrestClient').value  = p.client_id;
    document.getElementById('editPrestDesc').value    = p.description || '';
    document.getElementById('editPrestDebut').value   = p.date_debut || '';
    document.getElementById('editPrestFin').value     = p.date_fin || '';
    document.getElementById('editPrestModal').style.display = 'flex';
}

// ── Filtre tableau
function filterTable(tableId, query) {
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query.toLowerCase()) ? '' : 'none';
    });
}

// ── Fermer modal en cliquant à l'extérieur
window.onclick = function(e) {
    document.querySelectorAll('.modal').forEach(m => {
        if (e.target === m) m.style.display = 'none';
    });
};

// Pré-remplir la date selon la demande client
document.querySelector('#modalIntervention select[name="prestation_id"]').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const dates = {
        <?php foreach ($prestations as $p): ?>
        <?= $p['id'] ?>: '<?= $p['date_debut'] ?? '' ?>',
        <?php endforeach; ?>
    };
    const date = dates[this.value];
    if (date) {
        document.getElementById('dateIntervention').value = date;
    }
});

</script>

</body>
</html>
