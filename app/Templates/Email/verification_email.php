<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - VÉRIFICATION D'EMAIL
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Variables requises :
 * - $prenom : Prénom de l'utilisateur
 * - $url : URL de vérification
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

$titre = "Vérifiez votre email";
$prenomEchappe = htmlspecialchars($prenom);
$urlEchappee = htmlspecialchars($url);

ob_start();
?>
<h2 style="color: #0f172a; margin: 0 0 20px 0; font-size: 26px; font-weight: 600;">Bonjour <?= $prenomEchappe ?> ! 👋</h2>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;">
    Merci de vous être inscrit sur <strong style="color: #f59e0b;">ReVente-Auto</strong>, votre plateforme de vente de véhicules d'occasion de confiance.
</p>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 30px 0;">
    Pour activer votre compte et commencer à publier vos annonces, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous :
</p>

<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding: 10px 0;">
            <a href="<?= $urlEchappee ?>" style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);">
                ✓ Vérifier mon email
            </a>
        </td>
    </tr>
</table>

<p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
    ⏰ Ce lien est valide pendant <strong style="color: #0f172a;">24 heures</strong>. Après cette période, vous devrez demander un nouveau lien.
</p>

<p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0;">
    Si vous n'avez pas créé de compte sur ReVente-Auto, vous pouvez ignorer cet email en toute sécurité.
</p>
<?php
$contenu = ob_get_clean();
include __DIR__ . '/base.php';
