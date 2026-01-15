<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - MODÉRATION ANNONCE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Variables requises :
 * - $prenom : Prénom du propriétaire
 * - $marque : Marque du véhicule
 * - $modele : Modèle du véhicule
 * - $approuvee : Boolean - true si approuvée
 * - $raisonRefus : (optionnel) Raison du refus
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

$prenomEchappe = htmlspecialchars($prenom);
$vehiculeEchappe = htmlspecialchars($marque . ' ' . $modele);

if ($approuvee) {
    $titre = "Annonce approuvée !";
    $icone = '✅';
    $couleurBandeau = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    $message = "Bonne nouvelle ! Votre annonce pour <strong style=\"color: #f59e0b;\">$vehiculeEchappe</strong> a été approuvée par notre équipe de modération.";
    $sousTitre = 'Elle est maintenant visible par tous les visiteurs de ReVente-Auto.';
    $blocInfo = '';
} else {
    $titre = "Annonce non approuvée";
    $icone = '❌';
    $couleurBandeau = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
    $message = "Votre annonce pour <strong style=\"color: #f59e0b;\">$vehiculeEchappe</strong> n'a malheureusement pas été approuvée par notre équipe.";
    $sousTitre = 'Vous pouvez modifier votre annonce et la soumettre à nouveau.';
    $blocInfo = '';
    
    if (!empty($raisonRefus)) {
        $raisonEchappee = nl2br(htmlspecialchars($raisonRefus));
        $blocInfo = '
        <div style="background: #fef2f2; padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 20px 0;">
            <p style="color: #991b1b; font-size: 14px; font-weight: 600; margin: 0 0 10px 0;">Raison du refus :</p>
            <p style="color: #7f1d1d; font-size: 14px; line-height: 1.6; margin: 0;">' . $raisonEchappee . '</p>
        </div>';
    }
}

ob_start();
?>
<h2 style="color: #0f172a; margin: 0 0 20px 0; font-size: 26px; font-weight: 600;"><?= $icone ?> <?= $titre ?></h2>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;">Bonjour <?= $prenomEchappe ?>,</p>

<p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;"><?= $message ?></p>

<p style="color: #64748b; font-size: 15px; line-height: 1.7; margin: 0 0 20px 0;"><?= $sousTitre ?></p>

<?= $blocInfo ?>
<?php
$contenu = ob_get_clean();
include __DIR__ . '/base.php';
