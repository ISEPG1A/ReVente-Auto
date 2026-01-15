<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - BANNISSEMENT
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Variables requises :
 * - $prenom : Prénom de l'utilisateur
 * - $raison : Raison du bannissement
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

$titre = "Suspension de compte";
$sousTitre = "Notification importante";
$couleurBandeau = 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)';
$prenomEchappe = htmlspecialchars($prenom);
$raisonEchappee = htmlspecialchars($raison);
$emailContact = 'contact@revente-auto.fr';

ob_start();
?>
<p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">Bonjour <strong><?= $prenomEchappe ?></strong>,</p>

<p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
    Nous vous informons que votre compte ReVente-Auto a été <strong style="color: #dc2626;">suspendu</strong>.
</p>

<!-- Raison -->
<div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); padding: 25px; border-radius: 10px; border-left: 4px solid #dc2626; margin: 20px 0;">
    <p style="color: #7f1d1d; font-size: 13px; margin: 0 0 10px 0; font-weight: 600; text-transform: uppercase;">Raison de la suspension :</p>
    <p style="color: #991b1b; font-size: 15px; line-height: 1.7; margin: 0;"><?= $raisonEchappee ?></p>
</div>

<p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 20px 0;">
    Vous ne pourrez plus accéder à votre compte tant que cette suspension sera active.
</p>

<!-- Contestation -->
<div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 25px 0;">
    <p style="color: #374151; font-size: 14px; margin: 0 0 10px 0; font-weight: 600;">
        <i>⚖️ Vous souhaitez contester cette décision ?</i>
    </p>
    <p style="color: #4b5563; font-size: 14px; line-height: 1.6; margin: 0;">
        Vous pouvez nous contacter en répondant directement à cet email ou en écrivant à 
        <a href="mailto:<?= $emailContact ?>" style="color: #f59e0b; text-decoration: none;"><?= $emailContact ?></a>. 
        Veuillez expliquer clairement votre situation et nous examinerons votre demande.
    </p>
</div>
<?php
$contenu = ob_get_clean();
include __DIR__ . '/base.php';
