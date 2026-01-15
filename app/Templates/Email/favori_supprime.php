<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - FAVORI SUPPRIMÉ
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Variables requises :
 * - $prenomDestinataire : Prénom du destinataire
 * - $marque : Marque du véhicule
 * - $modele : Modèle du véhicule
 * - $url : URL de la galerie
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

$titre = "Véhicule favori indisponible";
$prenomEchappe = htmlspecialchars($prenomDestinataire);
$vehiculeEchappe = htmlspecialchars($marque . ' ' . $modele);
$urlEchappee = htmlspecialchars($url);

ob_start();
?>
<h2 style="color: #0f172a; margin: 0 0 20px 0; font-size: 26px; font-weight: 600;">❤️ Véhicule indisponible</h2>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;">
    Bonjour <?= $prenomEchappe ?>,
</p>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;">
    Le véhicule <strong style="color: #f59e0b;"><?= $vehiculeEchappe ?></strong> que vous aviez ajouté à vos favoris n'est plus disponible sur ReVente-Auto.
</p>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 30px 0;">
    Il a peut-être été vendu ou retiré par son propriétaire. Bonne nouvelle, d'autres véhicules similaires vous attendent !
</p>

<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding: 10px 0;">
            <a href="<?= $urlEchappee ?>" style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);">
                🔍 Découvrir d'autres véhicules
            </a>
        </td>
    </tr>
</table>
<?php
$contenu = ob_get_clean();
include __DIR__ . '/base.php';
