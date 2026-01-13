<?php
/**
 * Script pour générer un PDF CGU manuellement
 */

// C:\xampp\php\php.exe "C:\xampp\htdocs\test\ReVente-Auto\cron\generer_pdf_cgu.php"

// Charger l'application
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/autochargement.php';

try {
    $modele = new ModeleVersionCGU();
    
    echo "Génération du PDF CGU...\n";
    
    $nouvelleVersion = $modele->creerNouvelleVersion();
    
    if ($nouvelleVersion) {
        echo "\n✓ SUCCÈS !\n";
        echo "  • Fichier : {$nouvelleVersion['nom_fichier']}\n";
        echo "  • Taille  : " . ModeleVersionCGU::formaterTaille($nouvelleVersion['taille_fichier']) . "\n";
        echo "  • Chemin  : public/uploads/cgu-versions/{$nouvelleVersion['nom_fichier']}\n";
    } else {
        echo "\n✗ ERREUR : Impossible de créer le PDF.\n";
    }
} catch (Exception $e) {
    echo "✗ ERREUR : " . $e->getMessage() . "\n";
}
