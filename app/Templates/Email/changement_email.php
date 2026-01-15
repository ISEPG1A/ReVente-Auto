<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - CHANGEMENT D'ADRESSE EMAIL
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Variables requises :
 * - $prenom : Prénom de l'utilisateur
 * - $url : URL de confirmation
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

$titre = "Confirmez votre nouvelle adresse email";
$prenomEchappe = htmlspecialchars($prenom);
$urlEchappee = htmlspecialchars($url);

ob_start();
?>
<h2 style="color: #0f172a; margin: 0 0 20px 0; font-size: 26px; font-weight: 600;">Nouvelle adresse email</h2>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;">
    Bonjour <?= $prenomEchappe ?>,
</p>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 30px 0;">
    Vous avez demandé à changer votre adresse email sur <strong style="color: #f59e0b;">ReVente-Auto</strong>. Pour confirmer cette nouvelle adresse, cliquez sur le bouton ci-dessous :
</p>

<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding: 10px 0;">
            <a href="<?= $urlEchappee ?>" style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);">
                ✓ Confirmer mon nouvel email
            </a>
        </td>
    </tr>
</table>

<p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
    ⏰ Ce lien est valide pendant <strong style="color: #0f172a;">24 heures</strong>.
</p>

<p style="color: #dc2626; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0; padding: 15px; background-color: #fef2f2; border-left: 4px solid #dc2626; border-radius: 4px;">
    ⚠️ Si vous n'avez pas demandé ce changement, ignorez cet email. Votre adresse actuelle restera inchangée.
</p>
<?php
$contenu = ob_get_clean();
include __DIR__ . '/base.php';
