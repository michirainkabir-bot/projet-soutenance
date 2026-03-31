<?php
// =============================================================
// app/views/technicien/dashboard.php
// Converti depuis technicien.html
// =============================================================
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../helpers/auth.php';

requireRole('technicien');

$pdo  = getPDO();
$user = getUser();
$techId = $user['id'];

// Interventions du technicien connecté
$interventions = $pdo->prepare(
    "SELECT i.*, p.titre AS prestation_titre,
            CONCAT(c.nom,' ',c.prenom) AS client_nom,
            c.adresse AS client_lieu
     FROM interventions i
     JOIN prestations p  ON i.prestation_id = p.id
     JOIN utilisateurs c ON p.client_id = c.id
     WHERE i.technicien_id = :id
     ORDER BY i.date_intervention DESC"
);
$interventions->execute([':id' => $techId]);
$interventions = $interventions->fetchAll();

// Interventions du jour
$aujourdhui = date('Y-m-d');
$intv_jour = array_filter($interventions, fn($i) =>
    date('Y-m-d', strtotime($i['date_intervention'])) === $aujourdhui
);

// Statistiques
$total   = count($interventions);
$enCours = count(array_filter($interventions, fn($i) => $i['statut'] === 'en_cours'));
$termine = count(array_filter($interventions, fn($i) => $i['statut'] === 'termine'));

// Prestations disponibles (toutes)
$prestations = $pdo->query(
    "SELECT p.*, CONCAT(c.nom,' ',c.prenom) AS client_nom
     FROM prestations p JOIN utilisateurs c ON p.client_id=c.id
     ORDER BY p.created_at DESC"
)->fetchAll();

// Profil complet
$profil = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
$profil->execute([':id' => $techId]);
$profil = $profil->fetch();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function statutBadge(string $s): string {
    return match($s) {
        'planifie' => '<span class="attente">En attente</span>',
        'en_cours' => '<span class="en-cours">En cours</span>',
        'termine'  => '<span class="termine">Terminée</span>',
        default    => htmlspecialchars($s),
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/technicien.css">
    <title>Technicien — <?= APP_NAME ?></title>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">
        <img src="<?= BASE_URL ?>/public/images/logo.png" alt="logo" width="170px" height="150px">
    </div>
    <h2>Technicien Panel</h2>
    <ul>
        <li onclick="showSection('dashboard')"><i class="fa-solid fa-chart-line"></i> Dashboard</li>
        <li onclick="showSection('interventions')"><i class="fa-solid fa-screwdriver-wrench"></i> Mes Interventions</li>
        <li onclick="showSection('prestations')"><i class="fa-solid fa-briefcase"></i> Prestations</li>
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
            <h1>Tableau de bord Technicien</h1>
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
                <div><h3><?= $total ?></h3><p>Interventions assignées</p></div>
                <div class="iconBox blue"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            </div>
            <div class="card">
                <div><h3><?= $enCours ?></h3><p>En cours</p></div>
                <div class="iconBox orange"><i class="fa-solid fa-spinner"></i></div>
            </div>
            <div class="card">
                <div><h3><?= $termine ?></h3><p>Terminées</p></div>
                <div class="iconBox green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
        </div>

        <h2>Mes interventions récentes</h2>
        <section class="table-container">
            <table class="intervention-table">
                <thead>
                    <tr><th>ID</th><th>Client</th><th>Prestation</th><th>Date</th><th>Statut</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach (array_slice($interventions, 0, 5) as $i): ?>
                <tr>
                    <td><?= $i['id'] ?></td>
                    <td><?= e($i['client_nom']) ?></td>
                    <td><?= e($i['prestation_titre']) ?></td>
                    <td><?= date('d M Y', strtotime($i['date_intervention'])) ?></td>
                    <td><?= statutBadge($i['statut']) ?></td>
                    <td>
                        <?php if ($i['statut'] === 'planifie'): ?>
                            <button onclick="changerStatut(<?= $i['id'] ?>, 'en_cours')">Démarrer</button>
                        <?php elseif ($i['statut'] === 'en_cours'): ?>
                            <button onclick="ouvrirRapport(<?= $i['id'] ?>)">Terminer</button>
                        <?php else: ?>
                            <button disabled>Terminée</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($interventions)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#999">Aucune intervention assignée</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Interventions du jour -->
        <section class="interventions-jour">
            <h2>Interventions du jour</h2>
            <div class="intervention-list">
            <?php if (empty($intv_jour)): ?>
                <p style="color:#999;padding:16px">Aucune intervention prévue aujourd'hui.</p>
            <?php endif; ?>
            <?php foreach ($intv_jour as $i): ?>
                <div class="intervention-card">
                    <div class="heure">
                        <i class="fa-solid fa-clock"></i>
                        <?= date('H:i', strtotime($i['date_intervention'])) ?>
                    </div>
                    <div class="details">
                        <h3><?= e($i['prestation_titre']) ?></h3>
                        <p>Client : <?= e($i['client_nom']) ?></p>
                    </div>
                    <div class="<?= $i['statut'] === 'termine' ? 'statut termine' : ($i['statut'] === 'en_cours' ? 'en-cours' : 'attente') ?>">
                        <?= statutBadge($i['statut']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </section>
    </div>

    <!-- MES INTERVENTIONS -->
    <div id="interventions" class="section">
        <h2>Mes Interventions</h2>
        <section class="table-container">
            <table class="intervention-table">
                <thead>
                    <tr><th>ID</th><th>Client</th><th>Prestation</th><th>Date</th><th>Statut</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($interventions as $i): ?>
                <tr>
                    <td><?= $i['id'] ?></td>
                    <td><?= e($i['client_nom']) ?></td>
                    <td><?= e($i['prestation_titre']) ?></td>
                    <td><?= date('d M Y H:i', strtotime($i['date_intervention'])) ?></td>
                    <td><?= statutBadge($i['statut']) ?></td>
                    <td>
                        <?php if ($i['statut'] === 'planifie'): ?>
                            <button onclick="changerStatut(<?= $i['id'] ?>, 'en_cours')">Démarrer</button>
                        <?php elseif ($i['statut'] === 'en_cours'): ?>
                            <button onclick="ouvrirRapport(<?= $i['id'] ?>)">Terminer</button>
                        <?php else: ?>
                            <button disabled>Terminée</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($interventions)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#999">Aucune intervention</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- PRESTATIONS -->
    <div id="prestations" class="section">
        <h2>Prestations disponibles</h2>
        <section class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Prestation</th><th>Description</th><th>Montant</th><th>Statut</th></tr>
                </thead>
                <tbody>
                <?php foreach ($prestations as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= e($p['titre']) ?></td>
                    <td><?= e($p['description'] ?? '—') ?></td>
                    <td><?= number_format($p['montant'], 0, ',', ' ') ?> FCFA</td>
                    <td><?= statutBadge($p['statut']) ?></td>
                </tr>
                <?php endforeach; ?>
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
                ? 'Intervention terminée : ' . e($i['prestation_titre'])
                : 'Intervention assignée : ' . e($i['prestation_titre']) . ' — ' . date('d/m/Y', strtotime($i['date_intervention'])) ?>
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

<!-- MODAL : Rapport d'intervention (terminer) -->
<div id="rapportModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div class="modal-content" style="background:#fff;padding:30px;border-radius:12px;width:400px;">
        <h2>Rapport d'intervention</h2>
        <form action="<?= BASE_URL ?>/app/controllers/InterventionController.php?action=updateStatut" method="POST">
            <input type="hidden" name="id"     id="rapportId">
            <input type="hidden" name="statut" value="termine">
            <label>Rapport de clôture</label>
            <textarea name="rapport" id="rapportTexte" rows="5"
                      placeholder="Décrivez les travaux effectués..."
                      style="width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;margin:10px 0;resize:vertical;"
                      required></textarea>
            <button type="submit" style="width:100%;padding:10px;background:#27ae60;color:#fff;border:none;border-radius:6px;cursor:pointer;">
                Clôturer l'intervention
            </button>
        </form>
    </div>
</div>

<!-- FORM caché : changer statut rapidement (démarrer) -->
<form id="formStatut" action="<?= BASE_URL ?>/app/controllers/InterventionController.php?action=updateStatut" method="POST" style="display:none;">
    <input type="hidden" name="id"      id="fStatutId">
    <input type="hidden" name="statut"  id="fStatutVal">
    <input type="hidden" name="rapport" value="">
</form>

<script src="<?= BASE_URL ?>/public/js/technicien.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    showSection(urlParams.get('section') || 'dashboard');
});

// Démarrer rapidement (statut → en_cours)
function changerStatut(id, statut) {
    if (!confirm('Démarrer cette intervention ?')) return;
    document.getElementById('fStatutId').value  = id;
    document.getElementById('fStatutVal').value = statut;
    document.getElementById('formStatut').submit();
}

// Ouvrir le modal de rapport pour clôturer
function ouvrirRapport(id) {
    document.getElementById('rapportId').value    = id;
    document.getElementById('rapportTexte').value = '';
    document.getElementById('rapportModal').style.display = 'flex';
}

window.onclick = function(e) {
    const m = document.getElementById('rapportModal');
    if (e.target === m) m.style.display = 'none';
};
</script>
</body>
</html>