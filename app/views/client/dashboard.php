<?php
// =============================================================
// app/views/client/dashboard.php
// Converti depuis client.html
// =============================================================
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireRole('client');

$pdo      = getPDO();
$user     = getUser();
$clientId = $user['id'];

// Interventions du client
$interventions = $pdo->prepare(
    "SELECT i.*, p.titre AS prestation_titre,
            CONCAT(t.nom,' ',t.prenom) AS tech_nom,
            p.date_debut
     FROM interventions i
     JOIN prestations p  ON i.prestation_id = p.id
     JOIN utilisateurs t ON i.technicien_id = t.id
     WHERE p.client_id = :id
     ORDER BY i.date_intervention DESC"
);
$interventions->execute([':id' => $clientId]);
$interventions = $interventions->fetchAll();

// Statistiques
$nbDemandes = $pdo->prepare("SELECT COUNT(*) FROM prestations WHERE client_id=:id");
$nbDemandes->execute([':id' => $clientId]);
$nbDemandes = $nbDemandes->fetchColumn();

$nbEnCours = count(array_filter($interventions, fn($i) => $i['statut'] === 'en_cours'));
$nbTermine = count(array_filter($interventions, fn($i) => $i['statut'] === 'termine'));

// Interventions planifiées (pas encore terminées)
$planifiees = array_filter($interventions, fn($i) => $i['statut'] !== 'termine');

// Factures du client
$factures = $pdo->prepare(
    "SELECT f.*, p.titre AS prestation_titre
     FROM factures f JOIN prestations p ON f.prestation_id=p.id
     WHERE f.client_id = :id
     ORDER BY f.date_emission DESC"
);
$factures->execute([':id' => $clientId]);
$factures = $factures->fetchAll();

// Catalogue des prestations disponibles
$catalogue = $pdo->query(
    "SELECT DISTINCT titre, description, montant FROM prestations ORDER BY titre"
)->fetchAll();

// Profil complet
$profil = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
$profil->execute([':id' => $clientId]);
$profil = $profil->fetch();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function statutClient(string $s): string {
    return match($s) {
        'planifie' => '<span class="attente">Planifiée</span>',
        'en_cours' => '<span class="en-cours">En cours</span>',
        'termine'  => '<span class="termine">Terminée</span>',
        'impayee'  => '<span class="attente">En attente</span>',
        'payee'    => '<span class="termine">Payée</span>',
        default    => htmlspecialchars($s),
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/client.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Client — <?= APP_NAME ?></title>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <img src="<?= BASE_URL ?>/public/images/logo.png" alt="logo" width="170px" height="150px">
    </div>
    <h2>Client Panel</h2>
    <ul>
        <li onclick="showSection('dashboard')"><i class="fa-solid fa-chart-line"></i> Dashboard</li>
        <li onclick="showSection('demandes')"><i class="fa-solid fa-file-circle-plus"></i> Mes demandes</li>
        <li onclick="showSection('prestations')"><i class="fa-solid fa-briefcase"></i> Prestations</li>
        <li onclick="showSection('factures')"><i class="fa-solid fa-file-invoice"></i> Factures</li>
        <li onclick="showSection('notifications')"><i class="fa-solid fa-bell"></i> Notifications</li>
        <li onclick="showSection('profil')"><i class="fa-solid fa-user"></i> Mon Profil</li>
        <li onclick="window.location.href='<?= BASE_URL ?>/app/controllers/AuthController.php?action=logout'">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </li>
    </ul>
</div>

<div class="main">
    <header>
        <div class="topbar">
            <h1>Tableau de bord Client</h1>
            <div>
                <i class="fa-solid fa-bell"></i>
                <i class="fa-solid fa-user"></i>
                <?= e($user['prenom'] . ' ' . $user['nom']) ?>
            </div>
        </div>
    </header>

    <?php if ($flash): ?>
    <div style="margin:10px 20px;padding:12px 18px;border-radius:8px;
        background:<?= $flash['type']==='success' ? '#d4edda' : '#f8d7da' ?>;
        color:<?= $flash['type']==='success' ? '#155724' : '#721c24' ?>;font-size:14px;">
        <?= e($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- DASHBOARD -->
    <div id="dashboard" class="section active">
        <div class="cards">
            <div class="card">
                <div><h3><?= $nbDemandes ?></h3><p>Demandes envoyées</p></div>
                <div class="iconBox blue"><i class="fa-solid fa-file-circle-plus"></i></div>
            </div>
            <div class="card">
                <div><h3><?= $nbEnCours ?></h3><p>En cours</p></div>
                <div class="iconBox orange"><i class="fa-solid fa-spinner"></i></div>
            </div>
            <div class="card">
                <div><h3><?= $nbTermine ?></h3><p>Terminées</p></div>
                <div class="iconBox green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>

        <h2>Mes interventions récentes</h2>
        <section class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Technicien</th><th>Prestation</th><th>Date</th><th>Statut</th></tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($interventions, 0, 5) as $i): ?>
                <tr>
                    <td><?= $i['id'] ?></td>
                    <td><?= e($i['tech_nom']) ?></td>
                    <td><?= e($i['prestation_titre']) ?></td>
                    <td><?= date('d M Y', strtotime($i['date_intervention'])) ?></td>
                    <td><?= statutClient($i['statut']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($interventions)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#999">Aucune intervention</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Interventions planifiées -->
        <section class="interventions-planifiees">
            <h2>Interventions planifiées</h2>
            <div class="planning-list">
            <?php if (empty($planifiees)): ?>
                <p style="color:#999;padding:16px">Aucune intervention planifiée.</p>
            <?php endif; ?>
            <?php foreach ($planifiees as $i): ?>
            <div class="planning-card">
                <div class="planning-date">
                    <i class="fa-solid fa-calendar-days"></i>
                    <?= date('d M Y', strtotime($i['date_intervention'])) ?>
                </div>
                <div class="planning-details">
                    <h3><?= e($i['prestation_titre']) ?></h3>
                    <p>Technicien : <?= e($i['tech_nom']) ?></p>
                </div>
                <div class="planning-statut <?= $i['statut'] === 'en_cours' ? 'en-cours' : 'attente' ?>">
                    <?= statutClient($i['statut']) ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </section>

        <div class="demande-prestation">
            <button class="btn-add" onclick="openDemandeModal()">
                <i class="fa-solid fa-paper-plane"></i> Demander une prestation
            </button>
        </div>
    </div>

    <!-- MES DEMANDES -->
    <div id="demandes" class="section">
        <h2>Mes Demandes</h2>
        <section class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Prestation</th><th>Technicien</th><th>Date</th><th>Statut</th></tr>
                </thead>
                <tbody>
                <?php foreach ($interventions as $i): ?>
                <tr>
                    <td><?= $i['id'] ?></td>
                    <td><?= e($i['prestation_titre']) ?></td>
                    <td><?= e($i['tech_nom']) ?></td>
                    <td><?= date('d/m/Y', strtotime($i['date_intervention'])) ?></td>
                    <td><?= statutClient($i['statut']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($interventions)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#999">Aucune demande</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
        <div class="demande-prestation" style="margin-top:16px">
            <button class="btn-add" onclick="openDemandeModal()">
                <i class="fa-solid fa-paper-plane"></i> Nouvelle demande
            </button>
        </div>
    </div>

    <!-- PRESTATIONS DISPONIBLES -->
    <div id="prestations" class="section">
        <h2>Prestations disponibles</h2>
        <section class="table-container">
            <table>
                <thead>
                    <tr><th>#</th><th>Prestation</th><th>Description</th><th>Prix</th></tr>
                </thead>
                <tbody>
                <?php foreach ($catalogue as $k => $p): ?>
                <tr>
                    <td><?= $k + 1 ?></td>
                    <td><?= e($p['titre']) ?></td>
                    <td><?= e($p['description'] ?? '—') ?></td>
                    <td><?= number_format($p['montant'], 0, ',', ' ') ?> FCFA</td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($catalogue)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999">Aucune prestation disponible</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
        <div class="demande-prestation" style="margin-top:16px">
            <button class="btn-add" onclick="openDemandeModal()">
                <i class="fa-solid fa-paper-plane"></i> Demander une prestation
            </button>
        </div>
    </div>

    <!-- FACTURES -->
    <div id="factures" class="section">
        <h2>Mes Factures</h2>
        <section class="table-container">
            <table>
                <thead>
                    <tr><th>N° Facture</th><th>Prestation</th><th>Date</th><th>Montant TTC</th><th>Statut</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($factures as $f): ?>
                <tr>
                    <td><?= e($f['numero']) ?></td>
                    <td><?= e($f['prestation_titre']) ?></td>
                    <td><?= date('d/m/Y', strtotime($f['date_emission'])) ?></td>
                    <td><?= number_format($f['montant_ttc'], 0, ',', ' ') ?> FCFA</td>
                    <td><?= statutClient($f['statut']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/app/controllers/FactureController.php?action=telecharger&id=<?= $f['id'] ?>"
                           target="_blank" title="Télécharger">
                            <i class="fa-solid fa-file-pdf" style="color:#e74c3c;font-size:18px;cursor:pointer"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($factures)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#999">Aucune facture disponible</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- NOTIFICATIONS -->
    <div id="notifications" class="section">
        <h2>Notifications</h2>
        <?php foreach ($interventions as $i): ?>
        <p>
            <i class="fa-solid <?= $i['statut'] === 'termine' ? 'fa-circle-check' : 'fa-bell' ?>"></i>
            <?= $i['statut'] === 'termine'
                ? 'Votre intervention est terminée : ' . e($i['prestation_titre'])
                : 'Intervention planifiée : ' . e($i['prestation_titre']) . ' — ' . date('d/m/Y', strtotime($i['date_intervention'])) ?>
        </p>
        <?php endforeach; ?>
        <?php if (empty($interventions)): ?><p style="color:#999">Aucune notification</p><?php endif; ?>
    </div>

    <!-- PROFIL -->
    <div id="profil" class="section">
        <h2>Mon Profil</h2>
        <div class="profile-card">
            <p><b>Nom :</b> <?= e($profil['nom'] . ' ' . $profil['prenom']) ?></p>
            <p><b>Email :</b> <?= e($profil['email']) ?></p>
            <p><b>Téléphone :</b> <?= e($profil['telephone'] ?? '—') ?></p>
            <p><b>Adresse :</b> <?= e($profil['adresse'] ?? '—') ?></p>
        </div>
    </div>

</div><!-- /.main -->

<!-- MODAL : Demander une prestation -->
<div id="demandeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDemandeModal()">&times;</span>
        <h2>Demander une prestation</h2>
        <section class="table-container">
            <!-- NOTE : La demande envoie un message à l'admin. L'admin créera l'intervention. -->
            <form id="demandeForm" action="<?= BASE_URL ?>/app/controllers/DemandeController.php?action=envoyer" method="POST">
                <label>Prestation souhaitée</label><br>
                <select name="prestation_titre" id="prestation" required>
                    <?php foreach ($catalogue as $p): ?>
                    <option value="<?= e($p['titre']) ?>"><?= e($p['titre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <br><br>
                <label>Date souhaitée</label><br>
                <input type="date" name="date" required>

                <br><br>
                <label>Heure souhaitée</label><br>
                <input type="time" name="heure">

                <br><br>
                <label>Description / Problème</label><br>
                <textarea name="description" placeholder="Décrivez votre besoin..."></textarea>

                <br><br>
                <button type="submit">
                    <i class="fa-solid fa-paper-plane"></i> Envoyer la demande
                </button>
            </form>
        </section>
    </div>
</div>

<!-- MODAL : Succès demande -->
<div id="successModal" class="modal">
    <div class="modal-content success-box">
        <i class="fa-solid fa-circle-check success-icon"></i>
        <h2>Demande envoyée !</h2>
        <p>Votre demande a été envoyée avec succès.</p>
        <p>Un membre de notre équipe vous contactera prochainement.</p>
        <button onclick="closeSuccessModal()">OK</button>
    </div>
</div>

<script src="<?= BASE_URL ?>/public/js/client.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    showSection(urlParams.get('section') || 'dashboard');
});

function openDemandeModal()  { document.getElementById('demandeModal').style.display = 'flex'; }
function closeDemandeModal() { document.getElementById('demandeModal').style.display = 'none'; }
function closeSuccessModal() { document.getElementById('successModal').style.display = 'none'; }

document.getElementById('demandeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    closeDemandeModal();
    document.getElementById('successModal').style.display = 'flex';
    // En production, soumettre vraiment le formulaire via fetch ou supprimer le e.preventDefault()
});

window.onclick = function(e) {
    document.querySelectorAll('.modal').forEach(m => {
        if (e.target === m) m.style.display = 'none';
    });
};
</script>
</body>
</html>