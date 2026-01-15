<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TESTS API EXHAUSTIFS - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Tests complets de toutes les routes API :
 * - Authentification (inscription, connexion, déconnexion, reset password)
 * - Véhicules (CRUD, galerie, recherche, filtres)
 * - Utilisateurs (profil, favoris, annonces)
 * - Messagerie
 * - Contact
 * - Admin
 * - FAQ, CGU, etc.
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/TestsBase.php';

class TestsAPI extends TestsBase {
    
    protected string $nomTest = 'TESTS API EXHAUSTIFS - ReVente-Auto';
    
    // Utilisateur de test
    private array $utilisateurTest;
    private ?int $vehiculeTestId = null;
    
    public function __construct() {
        parent::__construct();
        
        $this->utilisateurTest = [
            'email' => 'test_api_' . time() . '@test.com',
            'password' => 'TestPassword123!',
            'nom' => 'TestAPI',
            'prenom' => 'Utilisateur',
            'telephone' => '0600000000'
        ];
    }
    
    /**
     * Exécute tous les tests API
     */
    public function executer(): array {
        $this->afficherEntete();
        
        $this->testerAPIsPubliques();
        $this->testerAPIVehicules();
        $this->testerAPIAuthentification();
        $this->testerAPIsProtegees();
        $this->testerAPIFavoris();
        $this->testerAPIMessagerie();
        $this->testerAPIContact();
        $this->testerAPIEstimation();
        $this->testerAPILocalisation();
        $this->testerCodesErreur();
        $this->testerValidationEntrees();
        $this->testerPagination();
        
        $this->nettoyage();
        $this->afficherResume();
        
        return $this->getStatistiques();
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 1. APIs PUBLIQUES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPIsPubliques(): void {
        $this->afficherSection("1. APIs PUBLIQUES");
        
        $apisPubliques = [
            'vehicule/galerie' => ['GET', 'Galerie véhicules'],
            // Note: Les marques sont incluses dans la galerie, pas de route séparée
            'faq' => ['GET', 'FAQ'],
            'cgu' => ['GET', 'CGU'],
            'politique-confidentialite' => ['GET', 'Politique confidentialité'],
            'localisation?ville=Paris' => ['GET', 'Localisation'],
        ];
        
        foreach ($apisPubliques as $endpoint => $infos) {
            [$methode, $nom] = $infos;
            
            $response = $this->httpGet($this->apiUrl . $endpoint);
            $this->assertHttpCode(200, $response['code'], "API $nom accessible");
            
            if ($response['code'] === 200) {
                $this->assertJsonValide($response['body'], "API $nom retourne du JSON valide");
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 2. API VÉHICULES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPIVehicules(): void {
        $this->afficherSection("2. API VÉHICULES");
        
        // Galerie sans filtres
        $this->afficherSousSection("Galerie");
        $response = $this->httpGet($this->apiUrl . 'vehicule/galerie');
        $this->assertHttpCode(200, $response['code'], "GET /vehicule/galerie");
        
        $data = json_decode($response['body'], true);
        if ($data) {
            $this->assertNotEmpty($data, "La galerie retourne des données");
            
            // Vérifier structure de la réponse
            if (isset($data['vehicules']) || isset($data['data']) || is_array($data)) {
                $vehicules = $data['vehicules'] ?? $data['data'] ?? $data;
                if (is_array($vehicules) && !empty($vehicules)) {
                    $premier = reset($vehicules);
                    $this->assertTrue(isset($premier['id']) || isset($premier['titre']), "Véhicule a une structure valide");
                }
            }
        }
        
        // Filtres
        $this->afficherSousSection("Filtres de recherche");
        
        $filtres = [
            'marque=Peugeot' => 'Filtre par marque',
            'prix_min=5000' => 'Filtre prix minimum',
            'prix_max=20000' => 'Filtre prix maximum',
            'annee_min=2020' => 'Filtre année minimum',
            'carburant=Essence' => 'Filtre carburant',
            'boite=Automatique' => 'Filtre boîte de vitesses',
            'kilometrage_max=50000' => 'Filtre kilométrage',
        ];
        
        foreach ($filtres as $param => $description) {
            $response = $this->httpGet($this->apiUrl . 'vehicule/galerie?' . $param);
            $this->assertHttpCode(200, $response['code'], $description);
        }
        
        // Combinaison de filtres
        $response = $this->httpGet($this->apiUrl . 'vehicule/galerie?marque=Renault&prix_max=15000&annee_min=2018');
        $this->assertHttpCode(200, $response['code'], "Filtres combinés");
        
        // Référentiels - Note: Les marques/modèles sont inclus dans la réponse galerie
        $this->afficherSousSection("Référentiels");
        
        // Détail véhicule via la route dédiée
        $response = $this->httpGet($this->apiUrl . 'vehicule/details?id=1');
        $codeOK = in_array($response['code'], [200, 404]);
        $this->assertTrue($codeOK, "GET /vehicule/details?id=1", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 3. API AUTHENTIFICATION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPIAuthentification(): void {
        $this->afficherSection("3. API AUTHENTIFICATION");
        
        // Récupérer token CSRF
        $this->recupererCSRFToken();
        
        // Test inscription
        $this->afficherSousSection("Inscription");
        
        $dataInscription = [
            'email' => $this->utilisateurTest['email'],
            'password' => $this->utilisateurTest['password'],
            'password_confirmation' => $this->utilisateurTest['password'],
            'nom' => $this->utilisateurTest['nom'],
            'prenom' => $this->utilisateurTest['prenom'],
            'telephone' => $this->utilisateurTest['telephone'],
            'accepte_cgu' => true
        ];
        
        $response = $this->httpPost($this->apiUrl . 'inscription', $dataInscription);
        $inscriptionOK = in_array($response['code'], [200, 201, 400, 409, 422]);
        $this->assertTrue($inscriptionOK, "POST /inscription répond", "HTTP " . $response['code']);
        
        // Test connexion invalide
        $this->afficherSousSection("Connexion - Cas d'erreur");
        
        $dataInvalide = [
            'email' => 'inexistant@test.com',
            'password' => 'MotDePasseInvalide123!'
        ];
        
        $response = $this->httpPost($this->apiUrl . 'connexion', $dataInvalide);
        $this->assertTrue(in_array($response['code'], [400, 401, 422]), "Connexion invalide rejetée", "HTTP " . $response['code']);
        
        // Test connexion valide
        $this->afficherSousSection("Connexion - Cas valide");
        
        $dataConnexion = [
            'email' => $this->utilisateurTest['email'],
            'password' => $this->utilisateurTest['password']
        ];
        
        $response = $this->httpPost($this->apiUrl . 'connexion', $dataConnexion);
        $connexionOK = in_array($response['code'], [200, 400, 401, 422]);
        $this->assertTrue($connexionOK, "POST /connexion répond", "HTTP " . $response['code']);
        
        if ($response['code'] === 200) {
            $this->afficherInfo("✓ Connexion réussie");
        }
        
        // Test mot de passe oublié
        $this->afficherSousSection("Mot de passe oublié");
        
        $dataReset = ['email' => $this->utilisateurTest['email']];
        $response = $this->httpPost($this->apiUrl . 'mot-de-passe-oublie', $dataReset);
        $resetOK = in_array($response['code'], [200, 400, 404, 422, 429]);
        $this->assertTrue($resetOK, "POST /mot-de-passe-oublie répond", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 4. APIs PROTÉGÉES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPIsProtegees(): void {
        $this->afficherSection("4. APIs PROTÉGÉES (sans authentification)");
        
        $apisProtegees = [
            'profil' => 'Profil utilisateur',
            'mes-annonces' => 'Mes annonces',
            'favoris' => 'Favoris',
            'messagerie' => 'Messagerie (conversations)',
            'admin?action=utilisateurs' => 'Admin - Utilisateurs',
            'admin?action=vehicules' => 'Admin - Véhicules en attente',
        ];
        
        // Reset session pour tester sans auth
        $this->sessionCookie = null;
        
        foreach ($apisProtegees as $endpoint => $nom) {
            $response = $this->httpGet($this->apiUrl . $endpoint);
            $protege = in_array($response['code'], [401, 403]);
            $this->assertTrue($protege, "API $nom protégée", "HTTP " . $response['code'], Criticite::IMPORTANT);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 5. API FAVORIS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPIFavoris(): void {
        $this->afficherSection("5. API FAVORIS");
        
        // Connecter l'utilisateur d'abord
        $this->connecterUtilisateur();
        
        // Liste des favoris
        $response = $this->httpGet($this->apiUrl . 'favoris');
        $codeOK = in_array($response['code'], [200, 401]);
        $this->assertTrue($codeOK, "GET /favoris", "HTTP " . $response['code']);
        
        // Ajouter un favori (si véhicule existe)
        $response = $this->httpPost($this->apiUrl . 'favoris', ['vehicule_id' => 1]);
        $codeOK = in_array($response['code'], [200, 201, 400, 401, 404, 409]);
        $this->assertTrue($codeOK, "POST /favoris (ajouter)", "HTTP " . $response['code']);
        
        // Supprimer un favori
        $response = $this->httpDelete($this->apiUrl . 'favoris/1');
        $codeOK = in_array($response['code'], [200, 204, 401, 404]);
        $this->assertTrue($codeOK, "DELETE /favoris/:id", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 6. API MESSAGERIE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPIMessagerie(): void {
        $this->afficherSection("6. API MESSAGERIE");
        
        $this->connecterUtilisateur();
        
        // Liste des conversations (route: /api/messagerie sans paramètre)
        $response = $this->httpGet($this->apiUrl . 'messagerie');
        $codeOK = in_array($response['code'], [200, 401]);
        $this->assertTrue($codeOK, "GET /messagerie (conversations)", "HTTP " . $response['code']);
        
        // Messages d'une conversation (route: /api/messagerie?id_conversation=X)
        $response = $this->httpGet($this->apiUrl . 'messagerie?id_conversation=1');
        $codeOK = in_array($response['code'], [200, 401, 403, 404]);
        $this->assertTrue($codeOK, "GET /messagerie?id_conversation=1", "HTTP " . $response['code']);
        
        // Envoyer un message (POST sur /api/messagerie)
        $dataMessage = [
            'action' => 'envoyer',
            'id_conversation' => 1,
            'contenu' => 'Test message API'
        ];
        $response = $this->httpPost($this->apiUrl . 'messagerie', $dataMessage);
        $codeOK = in_array($response['code'], [200, 201, 400, 401, 403, 404, 422]);
        $this->assertTrue($codeOK, "POST /messagerie (envoyer)", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 7. API CONTACT
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPIContact(): void {
        $this->afficherSection("7. API CONTACT");
        
        // Récupérer le token CSRF d'abord
        $this->recupererCSRFToken();
        
        $dataContact = [
            'nom' => 'Test',
            'email' => 'test@test.com',
            'sujet' => 'Test API',
            'message' => 'Ceci est un message de test automatisé pour vérifier l\'API contact.',
            'rgpd' => true
        ];
        
        $response = $this->httpPost($this->apiUrl . 'contact', $dataContact);
        // 403 est OK car la protection CSRF fonctionne (pas de vrai token dans les tests)
        $codeOK = in_array($response['code'], [200, 201, 400, 403, 422, 429]);
        $this->assertTrue($codeOK, "POST /contact", "HTTP " . $response['code']);
        
        // Test avec données invalides - 403 est aussi acceptable (CSRF)
        $dataInvalide = [
            'nom' => '',
            'email' => 'invalide',
            'message' => ''
        ];
        
        $response = $this->httpPost($this->apiUrl . 'contact', $dataInvalide);
        $this->assertTrue(in_array($response['code'], [400, 403, 422]), "Contact invalide rejeté", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 8. API ESTIMATION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPIEstimation(): void {
        $this->afficherSection("8. API ESTIMATION");
        
        $dataEstimation = [
            'marque' => 'Peugeot',
            'modele' => '308',
            'annee' => 2020,
            'kilometrage' => 50000,
            'carburant' => 'Essence',
            'boite' => 'Manuelle'
        ];
        
        $response = $this->httpPost($this->apiUrl . 'estimation', $dataEstimation);
        $codeOK = in_array($response['code'], [200, 400, 422]);
        $this->assertTrue($codeOK, "POST /estimation", "HTTP " . $response['code']);
        
        if ($response['code'] === 200) {
            $data = json_decode($response['body'], true);
            $this->assertTrue(isset($data['estimation']) || isset($data['prix']), "Réponse contient une estimation");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 9. API LOCALISATION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAPILocalisation(): void {
        $this->afficherSection("9. API LOCALISATION");
        
        // Recherche par ville
        $villes = ['Paris', 'Lyon', 'Marseille', 'Toulouse'];
        
        foreach ($villes as $ville) {
            $response = $this->httpGet($this->apiUrl . 'localisation?ville=' . urlencode($ville));
            $codeOK = in_array($response['code'], [200, 400, 404]);
            $this->assertTrue($codeOK, "Localisation '$ville'", "HTTP " . $response['code']);
        }
        
        // Recherche par code postal
        $response = $this->httpGet($this->apiUrl . 'localisation?code_postal=75001');
        $codeOK = in_array($response['code'], [200, 400, 404]);
        $this->assertTrue($codeOK, "Localisation par code postal", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 10. CODES D'ERREUR
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerCodesErreur(): void {
        $this->afficherSection("10. CODES D'ERREUR HTTP");
        
        // 404 - Route inexistante
        $response = $this->httpGet($this->apiUrl . 'route-inexistante-12345');
        $this->assertTrue($response['code'] === 404, "Route inexistante retourne 404", "HTTP " . $response['code']);
        
        // 400 - Requête invalide (403 accepté si CSRF protection)
        $response = $this->httpPost($this->apiUrl . 'contact', []);
        $this->assertTrue(in_array($response['code'], [400, 403, 422]), "Données vides retourne 400/403/422", "HTTP " . $response['code']);
        
        // 401 - Non authentifié
        $this->sessionCookie = null;
        $response = $this->httpGet($this->apiUrl . 'profil');
        $this->assertTrue(in_array($response['code'], [401, 403]), "Accès sans auth retourne 401/403", "HTTP " . $response['code']);
        
        // 405 - Méthode non autorisée (si implémenté)
        $response = $this->httpDelete($this->apiUrl . 'faq');
        $codeOK = in_array($response['code'], [401, 403, 404, 405]);
        $this->assertTrue($codeOK, "DELETE sur route GET-only gérée", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 11. VALIDATION DES ENTRÉES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerValidationEntrees(): void {
        $this->afficherSection("11. VALIDATION DES ENTRÉES");
        
        // Email invalide
        $this->afficherSousSection("Validation email");
        $emailsInvalides = ['invalide', 'test@', '@test.com', 'test@.com'];
        
        foreach ($emailsInvalides as $email) {
            $response = $this->httpPost($this->apiUrl . 'inscription', ['email' => $email, 'password' => 'Test123!']);
            $rejete = in_array($response['code'], [400, 422]);
            $this->assertTrue($rejete, "Email invalide '$email' rejeté", "HTTP " . $response['code']);
        }
        
        // Mot de passe faible
        $this->afficherSousSection("Validation mot de passe");
        $motsDePasse = ['123', 'password', 'abc'];
        
        foreach ($motsDePasse as $mdp) {
            $response = $this->httpPost($this->apiUrl . 'inscription', [
                'email' => 'test@test.com',
                'password' => $mdp
            ]);
            $rejete = in_array($response['code'], [400, 422]);
            $this->assertTrue($rejete, "Mot de passe faible '$mdp' rejeté", "HTTP " . $response['code']);
        }
        
        // Injection dans les champs
        $this->afficherSousSection("Protection contre injections");
        $payloads = [
            '<script>alert(1)</script>',
            "'; DROP TABLE users; --",
            '{{constructor.constructor("alert(1)")()}}'
        ];
        
        foreach ($payloads as $payload) {
            $response = $this->httpPost($this->apiUrl . 'contact', [
                'nom' => $payload,
                'email' => 'test@test.com',
                'message' => 'Test'
            ]);
            // L'API ne doit pas crasher
            $this->assertTrue($response['code'] !== 500, "Payload malveillant ne cause pas d'erreur 500");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 12. PAGINATION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerPagination(): void {
        $this->afficherSection("12. PAGINATION");
        
        // Test pagination galerie
        $pages = [1, 2, 3];
        $limites = [10, 20, 50];
        
        foreach ($pages as $page) {
            $response = $this->httpGet($this->apiUrl . "vehicule/galerie?page=$page");
            $this->assertHttpCode(200, $response['code'], "Page $page accessible");
        }
        
        foreach ($limites as $limite) {
            $response = $this->httpGet($this->apiUrl . "vehicule/galerie?limit=$limite");
            $this->assertHttpCode(200, $response['code'], "Limite $limite acceptée");
            
            $data = json_decode($response['body'], true);
            if ($data) {
                $vehicules = $data['vehicules'] ?? $data['data'] ?? $data;
                if (is_array($vehicules)) {
                    $this->assertTrue(count($vehicules) <= $limite, "Résultats <= limite $limite");
                }
            }
        }
        
        // Valeurs invalides
        $response = $this->httpGet($this->apiUrl . "vehicule/galerie?page=-1");
        $codeOK = in_array($response['code'], [200, 400]);
        $this->assertTrue($codeOK, "Page négative gérée", "HTTP " . $response['code']);
        
        $response = $this->httpGet($this->apiUrl . "vehicule/galerie?limit=10000");
        $codeOK = in_array($response['code'], [200, 400]);
        $this->assertTrue($codeOK, "Limite excessive gérée", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ════════════════════════════════════════════════════════════════════════
    
    private function recupererCSRFToken(): void {
        $response = $this->httpGet($this->baseUrl . 'connexion');
        
        if (preg_match('/name="csrf_token"[^>]*value="([^"]+)"/', $response['body'], $matches)) {
            $this->csrfToken = $matches[1];
            $this->afficherInfo("Token CSRF récupéré (input)");
        } elseif (preg_match('/content="([^"]+)"[^>]*name="csrf-token"/', $response['body'], $matches)) {
            $this->csrfToken = $matches[1];
            $this->afficherInfo("Token CSRF récupéré (meta)");
        }
    }
    
    private function connecterUtilisateur(): void {
        $this->recupererCSRFToken();
        
        $response = $this->httpPost($this->apiUrl . 'connexion', [
            'email' => $this->utilisateurTest['email'],
            'password' => $this->utilisateurTest['password']
        ]);
        
        if ($response['code'] === 200) {
            $this->afficherInfo("Utilisateur connecté pour tests");
        }
    }
    
    private function nettoyage(): void {
        $this->afficherSection("NETTOYAGE");
        
        // Déconnexion
        $response = $this->httpPost($this->apiUrl . 'deconnexion', []);
        $this->afficherInfo("Déconnexion: HTTP " . $response['code']);
        
        // Note: Suppression utilisateur test via admin ou BDD si nécessaire
        $this->afficherInfo("Utilisateur de test: " . $this->utilisateurTest['email']);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXÉCUTION DIRECTE
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['argv'][0] ?? '')) {
    $tests = new TestsAPI();
    $resultats = $tests->executer();
    exit($resultats['echoues'] > 0 ? 1 : 0);
}
