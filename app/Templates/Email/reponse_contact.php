<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - RÉPONSE CONTACT
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Variables requises :
 * - $nom : Nom du destinataire
 * - $sujetOriginal : Sujet du message original
 * - $messageOriginal : Message original
 * - $reponse : Réponse de l'admin
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

$titre = "Réponse à votre message";
$sousTitre = "Réponse à votre message";
$nomEchappe = htmlspecialchars($nom);
$sujetEchappe = htmlspecialchars($sujetOriginal);
$messageEchappe = nl2br(htmlspecialchars($messageOriginal));
$reponseEchappee = nl2br(htmlspecialchars($reponse));

ob_start();
?>
<p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">Bonjour <strong><?= $nomEchappe ?></strong>,</p>

<p style="color: #374151; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
    Merci de nous avoir contactés. Voici notre réponse à votre message concernant "<strong><?= $sujetEchappe ?></strong>" :
</p>

<!-- Réponse -->
<div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); padding: 25px; border-radius: 10px; border-left: 4px solid #10b981; margin: 20px 0;">
    <p style="color: #065f46; font-size: 15px; line-height: 1.7; margin: 0;"><?= $reponseEchappee ?></p>
</div>

<!-- Message original -->
<div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 25px 0;">
    <p style="color: #6b7280; font-size: 13px; margin: 0 0 10px 0; font-weight: 600;">Votre message original :</p>
    <p style="color: #4b5563; font-size: 14px; line-height: 1.6; margin: 0; font-style: italic;"><?= $messageEchappe ?></p>
</div>

<p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
    Si vous avez d'autres questions, n'hésitez pas à nous recontacter.
</p>
<?php
$contenu = ob_get_clean();
include __DIR__ . '/base.php';
