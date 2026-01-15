<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TESTS ADMINISTRATION - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Tests des fonctionnalités d'administration :
 * - Bannissement d'utilisateurs (avec suppression des annonces)
 * - Débannissement
 * - Changement de rôle (user ↔ admin)
 * - Statistiques et tableau de bord
 * - Logs d'activité avec IP publique
 * - Modération des véhicules
 * - Gestion des utilisateurs
 * 
 * @version 1.0
 * @date 15 janvier 2026
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/autochargement.php';
require_once __DIR__ . '/TestsBase.php';

class TestsAdmin extends TestsBase {
    private $modeleAdmin;
    private $modeleUtilisateur;
    private $modeleVehicule;
    
    // IDs pour les tests
    private $adminId;
    private $userId1;
    private $userId2;
    private $vehicleId1;
    
    public function __construct() {
        parent::__construct();
        $this->nomTest = 'Tests Administration';
        $this->modeleAdmin = new ModeleAdmin();
        $this->modeleUtilisateur = new ModeleUtilisateur();
        $this->modeleVehicule = new ModeleVehicule();
    }
    
    /**
     * Initialisation : créer des utilisateurs de test
     */
    private function initialiserDonnees() {
        // Créer un admin
        $this->adminId = $this->modeleUtilisateur->creer(
            'Admin', 
            'Test', 
            'admin_test_' . time() . '@example.com', 
            '0600000000', 
            'AdminTest123!'
        );
        
        // Promouvoir en admin
        $db = BaseDeDonnees::obtenirConnexion();
        $db->prepare("UPDATE users SET role = 'admin', email_verified_at = NOW() WHERE id = ?")->execute([$this->adminId]);
        
        // Créer 2 utilisateurs normaux
        $this->userId1 = $this->modeleUtilisateur->creer(
            'User1', 
            'Test', 
            'user1_test_' . time() . '@example.com', 
            '0600000001', 
            'UserTest123!'
        );
        
        $this->userId2 = $this->modeleUtilisateur->creer(
            'User2', 
            'Test', 
            'user2_test_' . time() . rand(1000, 9999) . '@example.com', 
            '0600000002', 
            'UserTest123!'
        );
        
        // Vérifier les emails
        $db->prepare("UPDATE users SET email_verified_at = NOW() WHERE id IN (?, ?)")->execute([$this->userId1, $this->userId2]);
        
        // Créer un véhicule pour user1
        $db->prepare("
            INSERT INTO vehicles (user_id, type_vehicule, marque, modele, annee, prix, km, 
                                  carburant, boite, ville, code_postal, status, created_at)
            VALUES (?, 'voiture', 'Renault', 'Clio', 2020, 12000, 50000, 
                    'essence', 'manuelle', 'Paris', '75001', 'public', NOW())
        ")->execute([$this->userId1]);
        
        $this->vehicleId1 = $db->lastInsertId();
    }
    
    /**
     * Nettoyage : supprimer les données de test
     */
    private function nettoyerDonnees() {
        $db = BaseDeDonnees::obtenirConnexion();
        
        // Supprimer les véhicules
        if ($this->vehicleId1) {
            $db->prepare("DELETE FROM vehicles WHERE id = ?")->execute([$this->vehicleId1]);
        }
        
        // Supprimer les utilisateurs
        foreach ([$this->adminId, $this->userId1, $this->userId2] as $id) {
            if ($id) {
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                $db->prepare("DELETE FROM admin_logs WHERE user_id = ? OR admin_id = ?")->execute([$id, $id]);
            }
        }
    }
    
    /**
     * TEST 1 : Bannissement d'un utilisateur
     */
    private function test_bannissement_utilisateur() {
        $this->initialiserDonnees();
        
        try {
            // Bannir l'utilisateur
            $resultat = $this->modeleAdmin->bannirUtilisateur(
                $this->userId1, 
                'Comportement inapproprié', 
                $this->adminId
            );
            
            $this->assertTrue($resultat, "Le bannissement devrait réussir");
            
            // Vérifier que l'utilisateur est banni
            $db = BaseDeDonnees::obtenirConnexion();
            $stmt = $db->prepare("SELECT banned_at, ban_reason, banned_by FROM users WHERE id = ?");
            $stmt->execute([$this->userId1]);
            $user = $stmt->fetch();
            
            $this->assertNotNull($user['banned_at'], "banned_at devrait être défini");
            $this->assertEquals('Comportement inapproprié', $user['ban_reason'], "La raison du ban devrait être enregistrée");
            $this->assertTrue($user['banned_by'] == $this->adminId, "L'admin bannisseur devrait être enregistré");
            
            // Vérifier que le véhicule a été supprimé
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM vehicles WHERE id = ?");
            $stmt->execute([$this->vehicleId1]);
            $count = $stmt->fetch()['count'];
            
            $this->assertEquals(0, $count, "Le véhicule devrait être supprimé après bannissement");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 2 : Débannissement d'un utilisateur
     */
    private function test_debannissement_utilisateur() {
        $this->initialiserDonnees();
        
        try {
            // D'abord bannir
            $this->modeleAdmin->bannirUtilisateur($this->userId1, 'Test', $this->adminId);
            
            // Puis débannir
            $resultat = $this->modeleAdmin->debannirUtilisateur($this->userId1);
            
            $this->assertTrue($resultat, "Le débannissement devrait réussir");
            
            // Vérifier que l'utilisateur n'est plus banni
            $db = BaseDeDonnees::obtenirConnexion();
            $stmt = $db->prepare("SELECT banned_at, ban_reason, banned_by FROM users WHERE id = ?");
            $stmt->execute([$this->userId1]);
            $user = $stmt->fetch();
            
            $this->assertNull($user['banned_at'], "banned_at devrait être NULL");
            $this->assertNull($user['ban_reason'], "ban_reason devrait être NULL");
            $this->assertNull($user['banned_by'], "banned_by devrait être NULL");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 3 : Changement de rôle user → admin
     */
    private function test_changement_role_vers_admin() {
        $this->initialiserDonnees();
        
        try {
            $resultat = $this->modeleAdmin->changerRole($this->userId1, 'admin');
            
            $this->assertTrue($resultat, "Le changement de rôle devrait réussir");
            
            // Vérifier le rôle
            $db = BaseDeDonnees::obtenirConnexion();
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$this->userId1]);
            $role = $stmt->fetch()['role'];
            
            $this->assertEquals('admin', $role, "Le rôle devrait être admin");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 4 : Changement de rôle admin → user (vide le poste)
     */
    private function test_changement_role_vers_user() {
        $this->initialiserDonnees();
        
        try {
            // D'abord promouvoir en admin avec un poste
            $db = BaseDeDonnees::obtenirConnexion();
            $db->prepare("UPDATE users SET role = 'admin', poste = 'Modérateur' WHERE id = ?")->execute([$this->userId1]);
            
            // Puis rétrograder
            $resultat = $this->modeleAdmin->changerRole($this->userId1, 'user');
            
            $this->assertTrue($resultat, "Le changement de rôle devrait réussir");
            
            // Vérifier que le rôle est user et le poste est NULL
            $stmt = $db->prepare("SELECT role, poste FROM users WHERE id = ?");
            $stmt->execute([$this->userId1]);
            $user = $stmt->fetch();
            
            $this->assertEquals('user', $user['role'], "Le rôle devrait être user");
            $this->assertNull($user['poste'], "Le poste devrait être vidé");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 5 : Rejet de rôle invalide
     */
    private function test_rejet_role_invalide() {
        $this->initialiserDonnees();
        
        try {
            $exceptionLevee = false;
            
            try {
                $this->modeleAdmin->changerRole($this->userId1, 'superadmin');
            } catch (Exception $e) {
                $exceptionLevee = true;
                $this->assertStringContainsString('invalide', strtolower($e->getMessage()));
            }
            
            $this->assertTrue($exceptionLevee, "Une exception devrait être levée pour un rôle invalide");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 5b : Impossible de promouvoir un utilisateur banni en admin
     */
    private function test_impossible_promouvoir_banni_admin() {
        $this->initialiserDonnees();
        
        try {
            // D'abord bannir l'utilisateur
            $this->modeleAdmin->bannirUtilisateur($this->userId2, 'Test bannissement', $this->adminId);
            
            $exceptionLevee = false;
            
            try {
                $this->modeleAdmin->changerRole($this->userId2, 'admin');
            } catch (Exception $e) {
                $exceptionLevee = true;
                $this->assertStringContainsString('banni', strtolower($e->getMessage()));
            }
            
            $this->assertTrue($exceptionLevee, "Une exception devrait être levée pour promouvoir un utilisateur banni");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 5c : Impossible de promouvoir un utilisateur non vérifié en admin
     */
    private function test_impossible_promouvoir_non_verifie_admin() {
        // Créer un utilisateur non vérifié
        $userIdNonVerifie = $this->modeleUtilisateur->creer(
            'NonVerifie',
            'Test',
            'nonverifie_' . time() . rand(1000, 9999) . '@example.com',
            '0600000099',
            'Test123!'
        );
        
        try {
            $exceptionLevee = false;
            
            try {
                $this->modeleAdmin->changerRole($userIdNonVerifie, 'admin');
            } catch (Exception $e) {
                $exceptionLevee = true;
                $this->assertStringContainsString('vérifié', strtolower($e->getMessage()));
            }
            
            $this->assertTrue($exceptionLevee, "Une exception devrait être levée pour promouvoir un utilisateur non vérifié");
            
        } finally {
            // Nettoyer
            $db = BaseDeDonnees::obtenirConnexion();
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userIdNonVerifie]);
        }
    }
    
    /**
     * TEST 6 : Logs d'activité avec IP
     */
    private function test_logs_activite_avec_ip() {
        $this->initialiserDonnees();
        
        try {
            // Simuler une IP publique (en production, viendrait de X-Forwarded-For)
            $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.45'; // IP de test
            
            // Ajouter un log
            $resultat = $this->modeleAdmin->ajouterLog(
                'utilisateur',
                'Test bannissement',
                ['user_id' => $this->userId1],
                $this->userId1,
                null,
                $this->adminId
            );
            
            $this->assertTrue($resultat, "L'ajout de log devrait réussir");
            
            // Vérifier que l'IP est enregistrée
            $db = BaseDeDonnees::obtenirConnexion();
            $stmt = $db->prepare("SELECT ip_address FROM admin_logs WHERE admin_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$this->adminId]);
            $log = $stmt->fetch();
            
            $this->assertNotEmpty($log['ip_address'], "L'IP devrait être enregistrée");
            
            // En environnement de test local, on accepte localhost ou l'IP simulée
            $ipValides = ['127.0.0.1', 'localhost', '203.0.113.45', '::1'];
            $this->assertTrue(
                in_array($log['ip_address'], $ipValides),
                "L'IP devrait être valide (reçu: {$log['ip_address']})"
            );
            
            // Nettoyer
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 7 : Statistiques dashboard admin
     */
    private function test_statistiques_dashboard() {
        $this->initialiserDonnees();
        
        try {
            $stats = $this->modeleAdmin->obtenirResume();
            
            // Vérifier la structure
            $this->assertIsArray($stats, "Les stats devraient être un tableau");
            $this->assertArrayHasKey('utilisateurs', $stats, "Stats devrait avoir la clé utilisateurs");
            $this->assertArrayHasKey('vehicules', $stats, "Stats devrait avoir la clé vehicules");
            $this->assertArrayHasKey('conversations', $stats, "Stats devrait avoir la clé conversations");
            
            // Vérifier les types
            $this->assertIsArray($stats['utilisateurs'], "utilisateurs devrait être un tableau");
            $this->assertIsArray($stats['vehicules'], "vehicules devrait être un tableau");
            $this->assertIsArray($stats['conversations'], "conversations devrait être un tableau");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 8 : Obtenir la liste des utilisateurs avec pagination
     */
    private function test_liste_utilisateurs_pagination() {
        $this->initialiserDonnees();
        
        try {
            $resultat = $this->modeleAdmin->obtenirUtilisateurs(1, 10);
            
            $this->assertIsArray($resultat, "Le résultat devrait être un tableau");
            $this->assertArrayHasKey('utilisateurs', $resultat, "Devrait avoir la clé utilisateurs");
            $this->assertArrayHasKey('total', $resultat, "Devrait avoir la clé total");
            $this->assertArrayHasKey('pages_total', $resultat, "Devrait avoir la clé pages_total");
            
            $this->assertGreaterThanOrEqual(2, $resultat['total'], "Devrait avoir au moins 2 utilisateurs de test");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 9 : Bannissement supprime les offres en cours
     */
    private function test_bannissement_annule_offres() {
        $this->initialiserDonnees();
        
        try {
            $db = BaseDeDonnees::obtenirConnexion();
            
            // Créer une conversation
            $db->prepare("
                INSERT INTO conversations (vehicle_id, buyer_id, seller_id, updated_at)
                VALUES (?, ?, ?, NOW())
            ")->execute([$this->vehicleId1, $this->userId2, $this->userId1]);
            $conversationId = $db->lastInsertId();
            
            // Créer une offre en attente
            $db->prepare("
                INSERT INTO offers (conversation_id, sender_id, amount, status, expires_at)
                VALUES (?, ?, 11000, 'pending', DATE_ADD(NOW(), INTERVAL 48 HOUR))
            ")->execute([$conversationId, $this->userId2]);
            $offerId = $db->lastInsertId();
            
            // Bannir l'utilisateur vendeur
            $this->modeleAdmin->bannirUtilisateur($this->userId1, 'Test', $this->adminId);
            
            // Vérifier que l'offre est annulée
            $stmt = $db->prepare("SELECT status FROM offers WHERE id = ?");
            $stmt->execute([$offerId]);
            $offer = $stmt->fetch();
            
            $this->assertEquals('cancelled', $offer['status'], "L'offre devrait être annulée après bannissement");
            
            // Nettoyer
            $db->prepare("DELETE FROM offers WHERE id = ?")->execute([$offerId]);
            $db->prepare("DELETE FROM conversations WHERE id = ?")->execute([$conversationId]);
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * TEST 10 : Modération véhicule (changement statut)
     */
    private function test_moderation_vehicule() {
        $this->initialiserDonnees();
        
        try {
            $resultat = $this->modeleAdmin->changerStatutVehicule($this->vehicleId1, 'refuse');
            
            $this->assertTrue($resultat, "Le changement de statut devrait réussir");
            
            // Vérifier le statut
            $db = BaseDeDonnees::obtenirConnexion();
            $stmt = $db->prepare("SELECT status FROM vehicles WHERE id = ?");
            $stmt->execute([$this->vehicleId1]);
            $status = $stmt->fetch()['status'];
            
            $this->assertEquals('refuse', $status, "Le statut du véhicule devrait être refusé");
            
        } finally {
            $this->nettoyerDonnees();
        }
    }
    
    /**
     * Exécuter tous les tests (méthode abstraite de TestsBase)
     */
    public function executer(): array {
        echo "\n🔧 Tests Administration\n";
        echo str_repeat('═', 80) . "\n\n";
        
        $this->test_bannissement_utilisateur();
        $this->test_debannissement_utilisateur();
        $this->test_changement_role_vers_admin();
        $this->test_changement_role_vers_user();
        $this->test_rejet_role_invalide();
        $this->test_impossible_promouvoir_banni_admin();
        $this->test_impossible_promouvoir_non_verifie_admin();
        $this->test_logs_activite_avec_ip();
        $this->test_statistiques_dashboard();
        $this->test_liste_utilisateurs_pagination();
        $this->test_bannissement_annule_offres();
        $this->test_moderation_vehicule();
        
        $this->afficherResume();
        
        return [
            'reussis' => $this->testsReussis,
            'echoues' => $this->testsEchoues,
            'total' => $this->totalTests,
            'pourcentage' => $this->totalTests > 0 ? round(($this->testsReussis / $this->totalTests) * 100, 1) : 0
        ];
    }
}

// Exécution si appelé directement
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $tests = new TestsAdmin();
    $resultats = $tests->executer();
    exit($resultats['echoues'] === 0 ? 0 : 1);
}
