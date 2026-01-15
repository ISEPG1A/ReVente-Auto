<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TEMPLATE EMAIL - BASE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Template de base pour tous les emails HTML.
 * Les templates spécifiques doivent définir $contenu avant d'inclure ce fichier.
 * 
 * Variables requises :
 * - $titre : Titre affiché dans le header et la balise title
 * - $contenu : Contenu HTML du body de l'email
 * - $couleurBandeau : (optionnel) Couleur du gradient header (par défaut: orange)
 * - $sousTitre : (optionnel) Sous-titre dans le header
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Valeurs par défaut
$couleurBandeau = $couleurBandeau ?? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
$sousTitre = $sousTitre ?? '';
$annee = date('Y');

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre) ?></title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);">
                    <!-- En-tête -->
                    <tr>
                        <td style="background: <?= $couleurBandeau ?>; padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -0.5px;">🚗 ReVente-Auto</h1>
                            <?php if ($sousTitre): ?>
                            <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 16px;"><?= htmlspecialchars($sousTitre) ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Contenu -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <?= $contenu ?>
                        </td>
                    </tr>
                    
                    <!-- Pied de page -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0;">
                                © <?= $annee ?> ReVente-Auto. Tous droits réservés.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
