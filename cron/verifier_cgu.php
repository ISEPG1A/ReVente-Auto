<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SCRIPT CRON - VÉRIFICATION ET ARCHIVAGE DES CGU
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce script est destiné à être exécuté quotidiennement par une tâche CRON.
 * Il vérifie si les CGU ont été modifiées depuis la dernière version
 * et génère un nouveau PDF d'archive si nécessaire.
 * 
 * Configuration CRON (à exécuter chaque soir à 23h00) :
 * 0 23 * * * /usr/bin/php /chemin/vers/cron/verifier_cgu.php
 * 
 * Sous Windows (Planificateur de tâches) :
 * C:\xampp\php\php.exe C:\xampp\htdocs\test\ReVente-Auto\cron\verifier_cgu.php
 * 
 * @author  ReVente Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Empêcher l'exécution via navigateur
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Ce script ne peut être exécuté que depuis la ligne de commande.');
}

// Charger l'application
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/app/autochargement.php';

// Début du script
$dateDebut = date('Y-m-d H:i:s');
echo "═══════════════════════════════════════════════════════════════════════\n";
echo " VÉRIFICATION CGU - $dateDebut\n";
echo "═══════════════════════════════════════════════════════════════════════\n\n";

try {
    $modele = new ModeleVersionCGU();
    
    // Vérifier si le contenu a changé
    echo "Vérification des modifications...\n";
    
    if ($modele->contenuAChange()) {
        echo "✓ Modifications détectées ! Création d'une nouvelle version...\n";
        
        $nouvelleVersion = $modele->creerNouvelleVersion();
        
        if ($nouvelleVersion) {
            echo "\n✓ SUCCÈS !\n";
            echo "  • Version : {$nouvelleVersion['version']}\n";
            echo "  • Fichier : {$nouvelleVersion['nom_fichier']}\n";
            echo "  • Taille  : " . ModeleVersionCGU::formaterTaille($nouvelleVersion['taille_fichier']) . "\n";
        } else {
            echo "\n✗ ERREUR : Impossible de créer la nouvelle version.\n";
            exit(1);
        }
    } else {
        echo "✓ Aucune modification détectée. Pas de nouvelle version nécessaire.\n";
    }
    
    // Afficher les statistiques
    $versions = $modele->obtenirToutesVersions();
    echo "\n───────────────────────────────────────────────────────────────────────\n";
    echo "STATISTIQUES\n";
    echo "───────────────────────────────────────────────────────────────────────\n";
    echo "  • Nombre de versions archivées : " . count($versions) . "\n";
    
    if (!empty($versions)) {
        $derniereVersion = $versions[0];
        echo "  • Dernière version : {$derniereVersion['version']} ({$derniereVersion['date_creation']})\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERREUR CRITIQUE : " . $e->getMessage() . "\n";
    error_log("[CRON CGU] Erreur : " . $e->getMessage());
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════════════════════\n";
echo " FIN DU SCRIPT - " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════════════\n";

exit(0);
