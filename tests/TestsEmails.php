<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TESTS D'ENVOI D'EMAILS - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Tests automatisés pour vérifier l'envoi de tous les templates d'emails :
 * - Vérification email
 * - Réinitialisation mot de passe
 * - Changement email
 * - Notifications (messages, favoris, modération)
 * - Proposition de prix
 * - Contact
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/TestsBase.php';

// Charger l'autochargement de l'application
require_once dirname(__DIR__) . '/app/autochargement.php';

class TestsEmails extends TestsBase {
    
    protected string $nomTest = 'TESTS D\'ENVOI D\'EMAILS - ReVente-Auto';
    
    // Email de test par défaut
    private string $emailDestinataire;
    
    // Délai entre les envois (secondes)
    private int $delaiEntreEnvois = 2;
    
    // Mode simulation (sans envoyer réellement)
    private bool $modeSimulation = false;
    
    public function __construct(string $emailDestinataire = null) {
        parent::__construct();
        $this->emailDestinataire = $emailDestinataire ?? 'marouane4142@gmail.com';
    }
    
    /**
     * Exécute tous les tests d'emails
     */
    public function executer(): array {
        $this->afficherEntete();
        
        // Vérifier que ServiceEmail existe
        if (!class_exists('ServiceEmail')) {
            $this->afficherSection("ERREUR DE CONFIGURATION");
            $this->assertTrue(false, "Classe ServiceEmail disponible", "", Criticite::CRITIQUE);
            $this->afficherResume();
            return $this->getStatistiques();
        }
        
        $this->afficherInfo("📧 Destinataire: " . $this->emailDestinataire);
        $this->afficherInfo("⏱ Délai entre envois: " . $this->delaiEntreEnvois . "s");
        
        $this->testerVerificationEmail();
        $this->testerResetMotDePasse();
        $this->testerChangementEmail();
        $this->testerNotificationNouveauMessage();
        $this->testerNotificationFavoriSupprime();
        $this->testerNotificationModeration();
        $this->testerNotificationBannissement();
        $this->testerPropositionPrix();
        $this->testerReponseContact();
        
        $this->afficherResume();
        
        return $this->getStatistiques();
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 1. VÉRIFICATION EMAIL
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerVerificationEmail(): void {
        $this->afficherSection("1. EMAIL DE VÉRIFICATION");
        
        $token = bin2hex(random_bytes(32));
        
        $succes = $this->envoyerEmail(function() use ($token) {
            return ServiceEmail::envoyerVerificationEmail(
                $this->emailDestinataire,
                'Test',
                $token
            );
        }, "Vérification email");
        
        $this->assertTrue($succes, "Email de vérification envoyé", "", Criticite::CRITIQUE);
        
        if ($succes) {
            $this->pause();
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 2. RÉINITIALISATION MOT DE PASSE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerResetMotDePasse(): void {
        $this->afficherSection("2. RÉINITIALISATION MOT DE PASSE");
        
        $token = bin2hex(random_bytes(32));
        
        $succes = $this->envoyerEmail(function() use ($token) {
            return ServiceEmail::envoyerResetMotDePasse(
                $this->emailDestinataire,
                'Test',
                $token
            );
        }, "Reset mot de passe");
        
        $this->assertTrue($succes, "Email de réinitialisation envoyé", "", Criticite::CRITIQUE);
        
        if ($succes) {
            $this->pause();
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 3. CHANGEMENT EMAIL
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerChangementEmail(): void {
        $this->afficherSection("3. CONFIRMATION CHANGEMENT EMAIL");
        
        $token = bin2hex(random_bytes(32));
        
        $succes = $this->envoyerEmail(function() use ($token) {
            return ServiceEmail::envoyerConfirmationChangementEmail(
                $this->emailDestinataire,
                'Test',
                $token
            );
        }, "Changement email");
        
        $this->assertTrue($succes, "Email de confirmation changement envoyé", "", Criticite::IMPORTANT);
        
        if ($succes) {
            $this->pause();
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 4. NOTIFICATION NOUVEAU MESSAGE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerNotificationNouveauMessage(): void {
        $this->afficherSection("4. NOTIFICATION NOUVEAU MESSAGE");
        
        $succes = $this->envoyerEmail(function() {
            return ServiceEmail::envoyerNotificationNouveauMessage(
                $this->emailDestinataire,
                'TestDestinataire',
                'Jean',
                'Dupont'
            );
        }, "Notification nouveau message");
        
        $this->assertTrue($succes, "Email notification nouveau message envoyé", "", Criticite::IMPORTANT);
        
        if ($succes) {
            $this->pause();
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 5. NOTIFICATION FAVORI SUPPRIMÉ
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerNotificationFavoriSupprime(): void {
        $this->afficherSection("5. NOTIFICATION FAVORI SUPPRIMÉ");
        
        $succes = $this->envoyerEmail(function() {
            return ServiceEmail::envoyerNotificationFavoriSupprime(
                $this->emailDestinataire,
                'TestDestinataire',
                'Renault',
                'Clio'
            );
        }, "Notification favori supprimé");
        
        $this->assertTrue($succes, "Email notification favori supprimé envoyé", "", Criticite::MOYEN);
        
        if ($succes) {
            $this->pause();
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 6. NOTIFICATION MODÉRATION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerNotificationModeration(): void {
        $this->afficherSection("6. NOTIFICATIONS MODÉRATION");
        
        // Test approbation
        $succes1 = $this->envoyerEmail(function() {
            return ServiceEmail::envoyerNotificationModeration(
                $this->emailDestinataire,
                'Test',
                'Peugeot',
                '308',
                true // Approuvée
            );
        }, "Modération - Approuvée");
        
        $this->assertTrue($succes1, "Email modération (approuvée) envoyé", "", Criticite::IMPORTANT);
        
        if ($succes1) {
            $this->pause();
        }
        
        // Test refus
        $succes2 = $this->envoyerEmail(function() {
            return ServiceEmail::envoyerNotificationModeration(
                $this->emailDestinataire,
                'Test',
                'BMW',
                'X5',
                false, // Refusée
                'Photos de mauvaise qualité. Merci de reprendre des photos plus nettes.'
            );
        }, "Modération - Refusée");
        
        $this->assertTrue($succes2, "Email modération (refusée) envoyé", "", Criticite::IMPORTANT);
        
        if ($succes2) {
            $this->pause();
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 7. NOTIFICATION BANNISSEMENT
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerNotificationBannissement(): void {
        $this->afficherSection("7. NOTIFICATION BANNISSEMENT");
        
        $succes = $this->envoyerEmail(function() {
            return ServiceEmail::envoyerNotificationBannissement(
                $this->emailDestinataire,
                'Test',
                "Violation des conditions d'utilisation : publication de contenu inapproprié."
            );
        }, "Notification bannissement");
        
        $this->assertTrue($succes, "Email notification bannissement envoyé", "", Criticite::IMPORTANT);
        
        if ($succes) {
            $this->pause();
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 8. PROPOSITION DE PRIX (OFFRE D'ACHAT)
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerPropositionPrix(): void {
        $this->afficherSection("8. PROPOSITION DE PRIX / OFFRE D'ACHAT");
        
        $vendeur = [
            'nom' => 'Vendeur',
            'prenom' => 'Jean'
        ];
        
        $acheteur = [
            'nom' => 'Acheteur',
            'prenom' => 'Sophie'
        ];
        
        $vehicule = [
            'id' => 42,
            'titre' => 'Peugeot 3008 GT Line 1.5 HDi 130ch',
            'prix' => 28500
        ];
        
        $prixPropose = 25000;
        $idConversation = 123; // ID de conversation fictif pour le test
        
        $this->afficherInfo("Véhicule: " . $vehicule['titre']);
        $this->afficherInfo("Prix annonce: " . number_format($vehicule['prix'], 0, ',', ' ') . " €");
        $this->afficherInfo("Prix proposé: " . number_format($prixPropose, 0, ',', ' ') . " €");
        $this->afficherInfo("Différence: -" . number_format($vehicule['prix'] - $prixPropose, 0, ',', ' ') . " €");
        
        // Note: Les URLs sont construites automatiquement par ServiceEmail
        $succes = $this->envoyerEmail(function() use ($vendeur, $acheteur, $vehicule, $prixPropose, $idConversation) {
            return ServiceEmail::envoyerPropositionPrix(
                $this->emailDestinataire,
                $vendeur,
                $acheteur,
                $vehicule,
                $prixPropose,
                $idConversation
            );
        }, "Proposition de prix");
        
        $this->assertTrue($succes, "Email proposition de prix envoyé", "", Criticite::CRITIQUE);
        
        if ($succes) {
            $this->pause();
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 9. RÉPONSE CONTACT
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerReponseContact(): void {
        $this->afficherSection("9. RÉPONSE AU MESSAGE DE CONTACT");
        
        $succes = $this->envoyerEmail(function() {
            return ServiceEmail::envoyerReponseContact(
                $this->emailDestinataire,
                'Test Utilisateur',
                'Question sur le site',
                "Bonjour, j'aimerais savoir si vous proposez des garanties sur les véhicules vendus sur votre plateforme. Merci d'avance pour votre réponse.",
                "Bonjour,\n\nMerci pour votre message. Les garanties dépendent du vendeur et sont indiquées sur chaque annonce. N'hésitez pas à contacter directement le vendeur via la messagerie pour plus de détails.\n\nCordialement,\nL'équipe ReVente-Auto"
            );
        }, "Réponse contact");
        
        $this->assertTrue($succes, "Email réponse contact envoyé", "", Criticite::IMPORTANT);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ════════════════════════════════════════════════════════════════════════
    
    /**
     * Envoie un email avec gestion des erreurs
     */
    private function envoyerEmail(callable $envoi, string $description): bool {
        if ($this->modeSimulation) {
            $this->afficherInfo("Mode simulation: $description - Non envoyé");
            return true;
        }
        
        echo Couleurs::CYAN . "📧 Envoi: $description... " . Couleurs::RESET;
        
        try {
            $resultat = $envoi();
            
            if ($resultat) {
                echo Couleurs::VERT . "✓ OK" . Couleurs::RESET . "\n";
                return true;
            } else {
                echo Couleurs::ROUGE . "✗ Échec (retour false)" . Couleurs::RESET . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo Couleurs::ROUGE . "✗ Erreur: " . $e->getMessage() . Couleurs::RESET . "\n";
            return false;
        }
    }
    
    /**
     * Pause entre les envois pour éviter le rate limiting
     */
    private function pause(): void {
        if ($this->delaiEntreEnvois > 0) {
            echo Couleurs::DIM . "   ⏳ Pause de {$this->delaiEntreEnvois}s..." . Couleurs::RESET . "\n";
            sleep($this->delaiEntreEnvois);
        }
    }
    
    /**
     * Définir l'email destinataire
     */
    public function setEmailDestinataire(string $email): self {
        $this->emailDestinataire = $email;
        return $this;
    }
    
    /**
     * Définir le délai entre envois
     */
    public function setDelaiEntreEnvois(int $secondes): self {
        $this->delaiEntreEnvois = $secondes;
        return $this;
    }
    
    /**
     * Activer le mode simulation (pas d'envoi réel)
     */
    public function setModeSimulation(bool $actif): self {
        $this->modeSimulation = $actif;
        return $this;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXÉCUTION DIRECTE
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['argv'][0] ?? '')) {
    // Récupérer l'email depuis les arguments (ignorer les options)
    $email = null;
    $args = $argv ?? [];
    array_shift($args); // Retirer le nom du script
    
    foreach ($args as $arg) {
        if (!str_starts_with($arg, '--') && filter_var($arg, FILTER_VALIDATE_EMAIL)) {
            $email = $arg;
            break;
        }
    }
    
    $tests = new TestsEmails($email);
    
    // Options depuis arguments
    if (in_array('--simulation', $argv ?? [])) {
        $tests->setModeSimulation(true);
    }
    
    if (in_array('--fast', $argv ?? [])) {
        $tests->setDelaiEntreEnvois(0);
    }
    
    $resultats = $tests->executer();
    exit($resultats['echoues'] > 0 ? 1 : 0);
}
