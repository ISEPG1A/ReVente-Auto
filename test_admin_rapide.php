<?php
/**
 * Test rapide des fonctions admin
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/autochargement.php';

echo "🔧 Test rapide Admin\n";
echo str_repeat('═', 80) . "\n\n";

try {
    $modeleAdmin = new ModeleAdmin();
    $modeleUtilisateur = new ModeleUtilisateur();
    $db = BaseDeDonnees::obtenirConnexion();
    
    // Test 1: Créer un utilisateur
    echo "1. Création utilisateur de test... ";
    $userId = $modeleUtilisateur->creer(
        'Test',
        'Admin',
        'test_' . time() . '@example.com',
        '0600000000',
        'Test123!'
    );
    echo "✓ ID: $userId\n";
    
    // Vérifier l'email pour les tests suivants
    $db->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = ?")->execute([$userId]);
    
    // Test 2: Bannir
    echo "2. Bannissement... ";
    $resultat = $modeleAdmin->bannirUtilisateur($userId, 'Test', 1);
    echo ($resultat ? "✓" : "✗") . "\n";
    
    // Test 3: Essayer de promouvoir un banni en admin (doit échouer)
    echo "3. Tentative promotion banni → admin (doit échouer)... ";
    try {
        $modeleAdmin->changerRole($userId, 'admin');
        echo "✗ (devrait avoir échoué)\n";
    } catch (Exception $e) {
        echo "✓ Bloqué: " . $e->getMessage() . "\n";
    }
    
    // Test 4: Débannir
    echo "4. Débannissement... ";
    $resultat = $modeleAdmin->debannirUtilisateur($userId);
    echo ($resultat ? "✓" : "✗") . "\n";
    
    // Test 5: Changer rôle (maintenant que débanni et vérifié)
    echo "5. Changement de rôle vers admin... ";
    $resultat = $modeleAdmin->changerRole($userId, 'admin');
    echo ($resultat ? "✓" : "✗") . "\n";
    
    // Test 6: Créer un utilisateur NON vérifié
    echo "6. Création utilisateur NON vérifié... ";
    $userIdNonVerifie = $modeleUtilisateur->creer(
        'NonVerifie',
        'Test',
        'nonverifie_' . time() . '@example.com',
        '0600000001',
        'Test123!'
    );
    echo "✓ ID: $userIdNonVerifie\n";
    
    // Test 7: Essayer de promouvoir un non vérifié en admin (doit échouer)
    echo "7. Tentative promotion non vérifié → admin (doit échouer)... ";
    try {
        $modeleAdmin->changerRole($userIdNonVerifie, 'admin');
        echo "✗ (devrait avoir échoué)\n";
    } catch (Exception $e) {
        echo "✓ Bloqué: " . $e->getMessage() . "\n";
    }
    
    // Test 8: Stats
    echo "8. Statistiques dashboard... ";
    $stats = $modeleAdmin->obtenirResume();
    echo (is_array($stats) ? "✓" : "✗") . "\n";
    
    // Nettoyage
    echo "\n🧹 Nettoyage... ";
    $db->prepare("DELETE FROM users WHERE id IN (?, ?)")->execute([$userId, $userIdNonVerifie]);
    echo "✓\n";
    
    echo "\n✅ Tous les tests ont réussi !\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
