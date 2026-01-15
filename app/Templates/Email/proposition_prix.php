<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - PROPOSITION DE PRIX
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Variables requises :
 * - $nomVendeur : Nom du vendeur (propriétaire de l'annonce)
 * - $prenomVendeur : Prénom du vendeur
 * - $nomAcheteur : Nom de l'acheteur (celui qui fait l'offre)
 * - $prenomAcheteur : Prénom de l'acheteur
 * - $titreVehicule : Titre de l'annonce du véhicule
 * - $prixAnnonce : Prix affiché de l'annonce
 * - $prixPropose : Prix proposé par l'acheteur
 * - $messageAcheteur : Message optionnel de l'acheteur
 * - $lienVehicule : Lien vers l'annonce
 * - $lienMessagerie : Lien vers la messagerie pour répondre
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

$titre = "Nouvelle proposition de prix";
$couleurBandeau = 'linear-gradient(135deg, #f59e0b 0%, #8b5cf6 100%)';

// Échapper les données
$prenomVendeurSafe = htmlspecialchars($prenomVendeur);
$prenomAcheteurSafe = htmlspecialchars($prenomAcheteur);
$nomAcheteurSafe = htmlspecialchars($nomAcheteur);
$titreVehiculeSafe = htmlspecialchars($titreVehicule);
$lienVehiculeSafe = htmlspecialchars($lienVehicule);
$lienMessagerieSafe = htmlspecialchars($lienMessagerie);

// Calculer la différence de prix
$difference = $prixAnnonce - $prixPropose;
$pourcentage = ($prixAnnonce > 0) ? round(($difference / $prixAnnonce) * 100, 1) : 0;
$estInferieur = $prixPropose < $prixAnnonce;

// Formater les prix
$prixAnnonceFmt = number_format($prixAnnonce, 0, ',', ' ');
$prixProposeFmt = number_format($prixPropose, 0, ',', ' ');
$differenceFmt = number_format(abs($difference), 0, ',', ' ');

ob_start();
?>
<h2 style="color: #0f172a; margin: 0 0 10px 0; font-size: 26px; font-weight: 600;">
    Bonjour <?= $prenomVendeurSafe ?> ! 💰
</h2>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 25px 0;">
    Bonne nouvelle ! Un acheteur potentiel est intéressé par votre véhicule et vous a fait une <strong style="color: #f59e0b;">proposition de prix</strong>.
</p>

<!-- Info véhicule -->
<div style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(139, 92, 246, 0.1)); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 20px; margin-bottom: 25px;">
    <p style="margin: 0 0 8px; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">
        📋 Véhicule concerné
    </p>
    <h3 style="margin: 0; font-size: 20px; color: #0f172a; font-weight: 600;">
        <?= $titreVehiculeSafe ?>
    </h3>
</div>

<!-- Comparaison des prix -->
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td width="48%" style="background: #f1f5f9; border-radius: 12px; padding: 20px; text-align: center; vertical-align: top;">
            <p style="margin: 0 0 5px; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">
                Votre prix
            </p>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: #1e293b;">
                <?= $prixAnnonceFmt ?> €
            </p>
        </td>
        <td width="4%"></td>
        <td width="48%" style="background: linear-gradient(135deg, #f59e0b, #8b5cf6); border-radius: 12px; padding: 20px; text-align: center; vertical-align: top;">
            <p style="margin: 0 0 5px; font-size: 11px; color: rgba(255,255,255,0.9); text-transform: uppercase; letter-spacing: 1px;">
                Prix proposé
            </p>
            <p style="margin: 0; font-size: 24px; font-weight: 700; color: white;">
                <?= $prixProposeFmt ?> €
            </p>
        </td>
    </tr>
</table>

<!-- Analyse de l'offre -->
<?php if ($estInferieur): ?>
<div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 10px; padding: 15px; margin-bottom: 25px; text-align: center;">
    <p style="margin: 0; color: #92400e; font-size: 14px;">
        📊 L'offre est <strong><?= $pourcentage ?>%</strong> en dessous de votre prix 
        <span style="color: #78716c;">(−<?= $differenceFmt ?> €)</span>
    </p>
</div>
<?php elseif ($prixPropose == $prixAnnonce): ?>
<div style="background: #dcfce7; border: 1px solid #22c55e; border-radius: 10px; padding: 15px; margin-bottom: 25px; text-align: center;">
    <p style="margin: 0; color: #166534; font-size: 14px;">
        🎉 L'offre correspond exactement à votre prix demandé !
    </p>
</div>
<?php else: ?>
<div style="background: #dcfce7; border: 1px solid #22c55e; border-radius: 10px; padding: 15px; margin-bottom: 25px; text-align: center;">
    <p style="margin: 0; color: #166534; font-size: 14px;">
        🎉 L'offre est supérieure à votre prix demandé ! (+<?= $differenceFmt ?> €)
    </p>
</div>
<?php endif; ?>

<!-- Info acheteur -->
<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 25px;">
    <p style="margin: 0 0 10px; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">
        👤 Proposition de
    </p>
    <p style="margin: 0; font-size: 18px; color: #0f172a; font-weight: 600;">
        <?= $prenomAcheteurSafe ?> <?= $nomAcheteurSafe ?>
    </p>
    
    <?php if (!empty($messageAcheteur)): ?>
    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
        <p style="margin: 0 0 10px; font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 1px;">
            💬 Message de l'acheteur
        </p>
        <p style="margin: 0; font-size: 15px; color: #475569; font-style: italic; line-height: 1.6; background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #8b5cf6;">
            "<?= nl2br(htmlspecialchars($messageAcheteur)) ?>"
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- Call to action -->
<p style="color: #475569; font-size: 15px; line-height: 1.6; margin: 0 0 20px 0; text-align: center;">
    Vous pouvez <strong>accepter</strong>, <strong>négocier</strong> ou <strong>refuser</strong> cette proposition :
</p>

<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
    <tr>
        <td align="center" style="padding: 10px 0;">
            <a href="<?= $lienMessagerieSafe ?>" style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #8b5cf6 100%); color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);">
                💬 Répondre à l'acheteur
            </a>
        </td>
    </tr>
</table>

<!-- Lien vers l'annonce -->
<div style="text-align: center; padding: 15px; background: #f1f5f9; border-radius: 8px;">
    <p style="margin: 0 0 8px; color: #64748b; font-size: 13px;">
        Voir votre annonce
    </p>
    <a href="<?= $lienVehiculeSafe ?>" style="color: #f59e0b; text-decoration: none; font-size: 14px; word-break: break-all;">
        <?= $lienVehiculeSafe ?>
    </a>
</div>

<?php
$contenu = ob_get_clean();

// Inclure le template de base
include __DIR__ . '/base.php';
?>
