<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture <?= e($facture['numero']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #333; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        .logo-zone h1 { font-size: 28px; color: #1e293b; font-weight: 900; letter-spacing: 2px; }
        .logo-zone p  { color: #666; font-size: 12px; margin-top: 4px; }
        .facture-info { text-align: right; }
        .facture-info h2 { font-size: 22px; color: #1e293b; }
        .facture-info p  { color: #666; font-size: 12px; margin-top: 4px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-top: 6px; }
        .badge.payee   { background: #d4edda; color: #155724; }
        .badge.impayee { background: #fff3cd; color: #856404; }
        .badge.annulee { background: #f8d7da; color: #721c24; }
        .divider { border: none; border-top: 2px solid #1e293b; margin: 20px 0; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 36px; }
        .partie { width: 45%; }
        .partie h3 { font-size: 11px; text-transform: uppercase; color: #999; letter-spacing: 1px; margin-bottom: 8px; }
        .partie p  { margin-bottom: 4px; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        thead tr { background: #1e293b; color: #fff; }
        thead th { padding: 12px 14px; text-align: left; font-size: 12px; }
        tbody tr:nth-child(even) { background: #f8f9fa; }
        tbody td { padding: 12px 14px; border-bottom: 1px solid #eee; }
        .totaux { margin-left: auto; width: 300px; }
        .totaux table { margin-bottom: 0; }
        .totaux td { padding: 8px 14px; }
        .totaux .ttc { background: #1e293b; color: #fff; font-weight: bold; font-size: 15px; }
        .footer { margin-top: 60px; text-align: center; color: #999; font-size: 11px; border-top: 1px solid #eee; padding-top: 20px; }
        @media print {
            body { padding: 20px; }
            .no-print { display: none; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>

<!-- Bouton impression (masqué à l'impression) -->
<div class="no-print" style="text-align:right;margin-bottom:20px">
    <button onclick="window.print()"
            style="padding:10px 20px;background:#1e293b;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">
        <span>🖨</span> Imprimer / Enregistrer en PDF
    </button>
</div>

<!-- EN-TÊTE -->
<div class="header">
    <div class="logo-zone">
        <h1>FGCL</h1>
        <p>Société de gestion de prestations de services</p>
        <p>Douala, Cameroun</p>
        <p>contact@fgcl.com | +237 699 000 000</p>
    </div>
    <div class="facture-info">
        <h2>FACTURE</h2>
        <p><strong><?= e($facture['numero']) ?></strong></p>
        <p>Date d'émission : <?= date('d/m/Y', strtotime($facture['date_emission'])) ?></p>
        <?php if ($facture['date_echeance']): ?>
        <p>Échéance : <?= date('d/m/Y', strtotime($facture['date_echeance'])) ?></p>
        <?php endif; ?>
        <span class="badge <?= $facture['statut'] ?>">
            <?= $facture['statut'] === 'payee' ? 'PAYÉE' : ($facture['statut'] === 'impayee' ? 'IMPAYÉE' : 'ANNULÉE') ?>
        </span>
    </div>
</div>

<hr class="divider">

<!-- PARTIES -->
<div class="parties">
    <div class="partie">
        <h3>Émetteur</h3>
        <p><strong>FGCL</strong></p>
        <p>Société de prestations de services</p>
        <p>Douala, Cameroun</p>
        <p>contact@fgcl.com</p>
    </div>
    <div class="partie" style="text-align:right">
        <h3>Destinataire</h3>
        <p><strong><?= e($facture['client_nom']) ?></strong></p>
        <?php if ($facture['client_email']): ?>
        <p><?= e($facture['client_email']) ?></p>
        <?php endif; ?>
        <?php if ($facture['client_tel']): ?>
        <p><?= e($facture['client_tel']) ?></p>
        <?php endif; ?>
        <?php if ($facture['client_adresse']): ?>
        <p><?= e($facture['client_adresse']) ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- TABLEAU PRESTATION -->
<table>
    <thead>
        <tr>
            <th>Description</th>
            <th style="text-align:right">Prix unitaire HT</th>
            <th style="text-align:center">Qté</th>
            <th style="text-align:right">Total HT</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong><?= e($facture['prestation_titre']) ?></strong>
                <?php if (!empty($facture['prestation_desc'])): ?>
                <br><small style="color:#666"><?= e($facture['prestation_desc']) ?></small>
                <?php endif; ?>
            </td>
            <td style="text-align:right"><?= number_format($facture['montant_ht'], 0, ',', ' ') ?> FCFA</td>
            <td style="text-align:center">1</td>
            <td style="text-align:right"><?= number_format($facture['montant_ht'], 0, ',', ' ') ?> FCFA</td>
        </tr>
    </tbody>
</table>

<!-- TOTAUX -->
<div class="totaux">
    <table>
        <tr>
            <td>Sous-total HT</td>
            <td style="text-align:right"><?= number_format($facture['montant_ht'], 0, ',', ' ') ?> FCFA</td>
        </tr>
        <tr>
            <td>TVA (<?= $facture['tva'] ?>%)</td>
            <td style="text-align:right">
                <?= number_format($facture['montant_ttc'] - $facture['montant_ht'], 0, ',', ' ') ?> FCFA
            </td>
        </tr>
        <tr class="ttc">
            <td style="padding:12px 14px">TOTAL TTC</td>
            <td style="text-align:right;padding:12px 14px">
                <?= number_format($facture['montant_ttc'], 0, ',', ' ') ?> FCFA
            </td>
        </tr>
    </table>
</div>

<!-- PIED DE PAGE -->
<div class="footer">
    <p>Merci de votre confiance. Ce document tient lieu de facture officielle.</p>
    <p style="margin-top:6px">FGCL — Douala, Cameroun — contact@fgcl.com</p>
</div>

<script>window.onload = function(){ window.print(); }</script>
</body>
</html>
