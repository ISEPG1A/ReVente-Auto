<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - NOUVEAU MESSAGE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Variables requises :
 * - $prenomDestinataire : Prénom du destinataire
 * - $prenomExpediteur : Prénom de l'expéditeur
 * - $nomExpediteur : Nom de l'expéditeur
 * - $url : URL de la messagerie
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

$titre = "Nouveau message";
$prenomDestEchappe = htmlspecialchars($prenomDestinataire);
$expediteurEchappe = htmlspecialchars($prenomExpediteur . ' ' . $nomExpediteur);
$urlEchappee = htmlspecialchars($url);

ob_start();
?>
<h2 style="color: #0f172a; margin: 0 0 20px 0; font-size: 26px; font-weight: 600;">💬 Nouveau message !</h2>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;">
    Bonjour <?= $prenomDestEchappe ?>,
</p>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 30px 0;">
    Vous avez reçu un nouveau message de <strong style="color: #f59e0b;"><?= $expediteurEchappe ?></strong> sur ReVente-Auto.
</p>

<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding: 10px 0;">
            <a href="<?= $urlEchappee ?>" style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);">
                📬 Voir ma messagerie
            </a>
        </td>
    </tr>
</table>

<p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0; text-align: center;">
    Pour des raisons de confidentialité, le contenu du message n'est pas affiché dans cet email.
</p>
<?php
$contenu = ob_get_clean();
include __DIR__ . '/base.php';
