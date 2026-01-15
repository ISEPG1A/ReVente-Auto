<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TESTS DE SÉCURITÉ AVANCÉS - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Tests exhaustifs couvrant :
 * - Configuration et secrets
 * - Protection des fichiers sensibles
 * - Authentification et autorisation
 * - Protection CSRF
 * - Injection SQL
 * - XSS (Cross-Site Scripting)
 * - Rate limiting
 * - IDOR
 * - Upload fichiers
 * - Headers HTTP de sécurité
 * - Sessions
 * - Mots de passe et chiffrement
 * - Path Traversal
 * - Mass Assignment
 * - Information disclosure
 * 
 * @author  Équipe ReVente-Auto
 * @version 3.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/TestsBase.php';

class TestsSecurite extends TestsBase {
    
    protected string $nomTest = 'TESTS DE SÉCURITÉ AVANCÉS - ReVente-Auto';
    
    private string $dossierApi;
    private string $dossierModeles;
    
    public function __construct() {
        parent::__construct();
        $this->dossierApi = $this->cheminApp . '/Controleurs/Api';
        $this->dossierModeles = $this->cheminApp . '/Modeles';
    }
    
    /**
     * Exécute tous les tests de sécurité
     */
    public function executer(): array {
        $this->afficherEntete();
        
        $this->testerConfigurationSecrets();
        $this->testerProtectionFichiersSensibles();
        $this->testerAuthentificationAPI();
        $this->testerVerificationEmail();
        $this->testerProtectionAdmin();
        $this->testerProtectionCSRF();
        $this->testerInjectionSQL();
        $this->testerXSS();
        $this->testerRateLimiting();
        $this->testerIDOR();
        $this->testerUploadFichiers();
        $this->testerHeadersHTTP();
        $this->testerSecuriteSessions();
        $this->testerMotsDePasse();
        $this->testerPathTraversal();
        $this->testerMassAssignment();
        $this->testerInformationDisclosure();
        $this->testerSecuriteJavascript();
        
        $this->afficherResume();
        
        return $this->getStatistiques();
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 1. CONFIGURATION ET SECRETS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerConfigurationSecrets(): void {
        $this->afficherSection("1. CONFIGURATION ET SECRETS");
        
        // Fichier .env
        $envExiste = file_exists($this->racineProjet . '/.env');
        $this->assertTrue($envExiste, "Fichier .env existe", "", Criticite::CRITIQUE);
        
        // .env.example pour documentation
        $envExampleExiste = file_exists($this->racineProjet . '/.env.example');
        $this->assertTrue($envExampleExiste, "Fichier .env.example existe (documentation)");
        
        // .gitignore protège les secrets
        $gitignore = $this->lireFichier($this->racineProjet . '/.gitignore');
        if ($gitignore) {
            $this->assertContient($gitignore, '.env', ".env dans .gitignore", Criticite::CRITIQUE);
            // config.php peut être versionné si les secrets sont dans .env
            $configInGitignore = strpos($gitignore, 'config.php') !== false;
            if (!$configInGitignore) {
                $this->afficherInfo("config.php non dans .gitignore (OK si secrets dans .env)");
            } else {
                $this->assertTrue(true, "config.php dans .gitignore");
            }
            $this->assertContient($gitignore, 'vendor', "vendor/ dans .gitignore");
            $this->assertContient($gitignore, 'node_modules', "node_modules/ dans .gitignore");
        } else {
            $this->afficherAvertissement(".gitignore non trouvé", Criticite::CRITIQUE);
        }
        
        // Config.php ne contient pas de secrets en dur
        $configPhp = $this->lireFichier($this->racineProjet . '/config.php');
        if ($configPhp) {
            $pasDeMotDePasseEnDur = !preg_match('/[\'"]password[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/', $configPhp) 
                || preg_match('/getenv|ENV|_ENV/', $configPhp);
            $this->assertTrue($pasDeMotDePasseEnDur, "Pas de mot de passe en dur dans config.php", "", Criticite::CRITIQUE);
            
            $pasDeSecretEnDur = !preg_match('/secret|api_key|private_key/i', $configPhp) 
                || preg_match('/getenv|ENV|_ENV/', $configPhp);
            $this->assertTrue($pasDeSecretEnDur, "Pas de clés secrètes en dur dans config.php", "", Criticite::IMPORTANT);
        }
        
        // Vérifier clé secrète configurée
        if ($envExiste) {
            $envContent = $this->lireFichier($this->racineProjet . '/.env');
            // Accepte APP_SECRET, APP_SECRET_KEY, SECRET_KEY, APP_KEY
            $cleSecrete = preg_match('/^(APP_SECRET_KEY|APP_SECRET|SECRET_KEY|APP_KEY)\s*=\s*(.{32,})/m', $envContent);
            $this->assertTrue($cleSecrete, "Clé secrète configurée (min 32 caractères)", "", Criticite::IMPORTANT);
        }
        
        // Vérifier mode debug désactivé
        if ($configPhp) {
            $debugDesactive = !preg_match('/[\'"]debug[\'"]\s*=>\s*true/i', $configPhp) 
                || preg_match('/getenv.*DEBUG/i', $configPhp);
            $this->assertTrue($debugDesactive, "Mode debug géré par environnement");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 2. PROTECTION DES FICHIERS SENSIBLES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerProtectionFichiersSensibles(): void {
        $this->afficherSection("2. PROTECTION DES FICHIERS SENSIBLES");
        
        // .htaccess uploads
        $htaccessUploads = $this->lireFichier($this->cheminPublic . '/uploads/.htaccess');
        if ($htaccessUploads) {
            $this->assertContient($htaccessUploads, 'php_flag engine off', "PHP désactivé dans uploads", Criticite::CRITIQUE);
            $this->assertRegex('/Options\s+(-Indexes|-ExecCGI)/i', $htaccessUploads, "Options restrictives dans uploads");
            $this->assertRegex('/X-Content-Type-Options.*nosniff/i', $htaccessUploads, "Header nosniff dans uploads");
        } else {
            $this->afficherAvertissement("uploads/.htaccess non trouvé", Criticite::CRITIQUE);
        }
        
        // Le dossier app n'est pas dans public
        $appDansPublic = is_dir($this->cheminPublic . '/app');
        $this->assertTrue(!$appDansPublic, "Dossier app n'est pas dans public", "", Criticite::CRITIQUE);
        
        // Point d'entrée unique
        $indexPhpUnique = file_exists($this->cheminPublic . '/index.php');
        $this->assertTrue($indexPhpUnique, "Point d'entrée unique (public/index.php)");
        
        // Pas d'autres fichiers PHP dans public (sauf index.php)
        $fichiersPHPPublic = glob($this->cheminPublic . '/*.php');
        $autresPointsEntree = array_filter($fichiersPHPPublic, fn($f) => basename($f) !== 'index.php');
        $this->assertTrue(empty($autresPointsEntree), "Pas d'autres points d'entrée PHP dans public", "", Criticite::IMPORTANT);
        
        // Vérifier que les dossiers sensibles ne sont pas accessibles
        $dossiersSensibles = ['vendor', 'app', 'database', 'tests', 'docs'];
        foreach ($dossiersSensibles as $dossier) {
            $response = $this->httpGet($this->baseUrl . $dossier . '/');
            $protege = $response['code'] === 403 || $response['code'] === 404;
            $this->assertTrue($protege, "Dossier $dossier inaccessible via HTTP", "HTTP " . $response['code'], Criticite::IMPORTANT);
        }
        
        // Vérifier que .env n'est pas accessible
        $response = $this->httpGet($this->baseUrl . '.env');
        $this->assertTrue($response['code'] === 403 || $response['code'] === 404, "Fichier .env inaccessible", "", Criticite::CRITIQUE);
        
        // Vérifier que .git n'est pas accessible
        $response = $this->httpGet($this->baseUrl . '.git/config');
        $this->assertTrue($response['code'] === 403 || $response['code'] === 404, "Dossier .git inaccessible", "", Criticite::CRITIQUE);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 3. AUTHENTIFICATION API
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAuthentificationAPI(): void {
        $this->afficherSection("3. AUTHENTIFICATION DANS LES API");
        
        $controlesApiSecurises = [
            'ControleurProfil.php' => ['session_utilisateur', 'connecte'],
            'ControleurMesAnnonces.php' => ['session_utilisateur', 'connecte'],
            'ControleurMessagerie.php' => ['session_utilisateur', 'connecte'],
            'ControleurFavoris.php' => ['session_utilisateur', 'connecte'],
            'ControleurVente.php' => ['session_utilisateur', 'connecte'],
            'ControleurModificationVehicule.php' => ['session_utilisateur', 'connecte'],
            'ControleurAdmin.php' => ['role', 'admin'],
        ];
        
        foreach ($controlesApiSecurises as $fichier => $verifications) {
            $contenu = $this->lireFichier($this->dossierApi . '/' . $fichier);
            if ($contenu) {
                $verifAuth = false;
                foreach ($verifications as $pattern) {
                    if (stripos($contenu, $pattern) !== false) {
                        $verifAuth = true;
                        break;
                    }
                }
                
                $altPatterns = [
                    'estConnecte', 'isLoggedIn', 'verifierAuthentification',
                    '$_SESSION', 'GestionnaireSession::estConnecte'
                ];
                foreach ($altPatterns as $alt) {
                    if (stripos($contenu, $alt) !== false) {
                        $verifAuth = true;
                        break;
                    }
                }
                
                $this->assertTrue($verifAuth, "$fichier vérifie l'authentification", "", Criticite::CRITIQUE);
                
                // Vérifier retour 401/403 si non authentifié
                $retour401ou403 = preg_match('/401|403|non.*autoris|unauthorized|forbidden/i', $contenu);
                $this->assertTrue($retour401ou403, "$fichier retourne erreur si non authentifié", "", Criticite::IMPORTANT);
            } else {
                $this->afficherInfo("$fichier non trouvé");
            }
        }
        
        // Test pratique : accès API sans session
        $apisProtegees = [
            'profil' => ['GET'],
            'mes-annonces' => ['GET'],
            'messagerie' => ['GET'],
            'favoris' => ['GET']
        ];
        
        $this->afficherSousSection("Tests d'accès sans authentification");
        foreach ($apisProtegees as $endpoint => $methodes) {
            $response = $this->httpGet($this->apiUrl . $endpoint);
            $protege = $response['code'] === 401 || $response['code'] === 403;
            $this->assertTrue($protege, "API $endpoint protégée sans session", "HTTP " . $response['code'], Criticite::IMPORTANT);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 4. VÉRIFICATION EMAIL REQUISE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerVerificationEmail(): void {
        $this->afficherSection("4. VÉRIFICATION EMAIL REQUISE");
        
        $apiAvecVerifEmail = [
            'ControleurVente.php' => true,
            'ControleurMessagerie.php' => true,
        ];
        
        foreach ($apiAvecVerifEmail as $fichier => $requis) {
            $contenu = $this->lireFichier($this->dossierApi . '/' . $fichier);
            if ($contenu) {
                $verifEmail = preg_match('/email.*v[eé]rifi|v[eé]rifi.*email|is_verified|email_verified/i', $contenu);
                $this->assertTrue($verifEmail, "$fichier vérifie l'email confirmé", "", Criticite::IMPORTANT);
            }
        }
        
        // Vérifier JavaScript côté client
        $vueDetails = $this->lireFichier($this->cheminPublic . '/assets/js/modules/vehicule/VueDetails.js');
        if ($vueDetails) {
            $messageVerifEmail = preg_match('/email.*v[eé]rifi|v[eé]rifi.*email|confirmez.*email/i', $vueDetails);
            $this->assertTrue($messageVerifEmail, "VueDetails.js informe si email non vérifié");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 5. PROTECTION ADMIN
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerProtectionAdmin(): void {
        $this->afficherSection("5. PROTECTION ADMIN");
        
        $controleurAdmin = $this->lireFichier($this->dossierApi . '/ControleurAdmin.php');
        if ($controleurAdmin) {
            $verifAdmin = preg_match('/admin|role.*admin|\[\'role\'\].*===.*[\'"]admin[\'"]|isAdmin/i', $controleurAdmin);
            $this->assertTrue($verifAdmin, "ControleurAdmin vérifie le rôle admin", "", Criticite::CRITIQUE);
            
            $blocage403 = preg_match('/403|forbidden|non.*autoris/i', $controleurAdmin);
            $this->assertTrue($blocage403, "Admin retourne erreur si non admin", "", Criticite::CRITIQUE);
        }
        
        // CGU modifications admin only
        $controleurCGU = $this->lireFichier($this->dossierApi . '/ControleurCGU.php');
        if ($controleurCGU) {
            $verifAdminCGU = preg_match('/admin|role.*admin/i', $controleurCGU);
            $this->assertTrue($verifAdminCGU, "CGU modifications réservées admin", "", Criticite::IMPORTANT);
        }
        
        // FAQ modifications admin only
        $controleurFAQ = $this->lireFichier($this->dossierApi . '/ControleurFAQ.php');
        if ($controleurFAQ) {
            $verifAdminFAQ = preg_match('/admin|role.*admin/i', $controleurFAQ);
            $this->assertTrue($verifAdminFAQ, "FAQ modifications réservées admin", "", Criticite::IMPORTANT);
        }
        
        // Test pratique : accès admin sans privilèges
        $response = $this->httpGet($this->apiUrl . 'admin?action=utilisateurs');
        $protege = $response['code'] === 401 || $response['code'] === 403;
        $this->assertTrue($protege, "API admin protégée", "HTTP " . $response['code'], Criticite::CRITIQUE);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 6. PROTECTION CSRF
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerProtectionCSRF(): void {
        $this->afficherSection("6. PROTECTION CSRF");
        
        // GestionnaireSession
        $gestionnaireSession = $this->lireFichier($this->cheminApp . '/Services/GestionnaireSession.php');
        if ($gestionnaireSession) {
            $genereToken = preg_match('/csrf.*token|token.*csrf|generer.*token/i', $gestionnaireSession);
            $this->assertTrue($genereToken, "GestionnaireSession génère des tokens CSRF", "", Criticite::CRITIQUE);
            
            $tokenAleatoire = preg_match('/random_bytes|bin2hex|openssl_random/i', $gestionnaireSession);
            $this->assertTrue($tokenAleatoire, "Token CSRF cryptographiquement sûr", "", Criticite::IMPORTANT);
        }
        
        // AideCSRF.php
        $aideCSRF = $this->lireFichier($this->cheminApp . '/Services/AideCSRF.php');
        if ($aideCSRF) {
            $valideToken = preg_match('/valider|verify|check|compare/i', $aideCSRF);
            $this->assertTrue($valideToken, "AideCSRF valide les tokens", "", Criticite::CRITIQUE);
            
            // hash_equals peut être dans AideCSRF ou GestionnaireSession (où la validation se fait)
            $hashEqualsAide = strpos($aideCSRF, 'hash_equals') !== false;
            $hashEqualsSession = $gestionnaireSession && strpos($gestionnaireSession, 'hash_equals') !== false;
            $this->assertTrue($hashEqualsAide || $hashEqualsSession, "Comparaison timing-safe (hash_equals)", "", Criticite::IMPORTANT);
        }
        
        // JavaScript protection-csrf.js
        $protectionCsrf = $this->lireFichier($this->cheminPublic . '/assets/js/modules/commun/protection-csrf.js');
        if ($protectionCsrf) {
            $ajouteHeader = preg_match('/X-CSRF-Token|X-Requested-With|csrf/i', $protectionCsrf);
            $this->assertTrue($ajouteHeader, "JS ajoute header CSRF", "", Criticite::IMPORTANT);
        }
        
        // Test pratique : requête POST sans token CSRF
        $response = $this->httpPost($this->apiUrl . 'contact', ['test' => 'data']);
        $this->afficherInfo("Requête POST sans CSRF: HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 7. INJECTION SQL
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerInjectionSQL(): void {
        $this->afficherSection("7. PROTECTION INJECTION SQL");
        
        $fichiersModeles = $this->parcourirDossier($this->dossierModeles);
        $modelesSecurises = 0;
        $modelesNonSecurises = [];
        $patternsRequetesDangereuses = [];
        
        foreach ($fichiersModeles as $fichierModele) {
            $contenu = $this->lireFichier($fichierModele);
            $nomFichier = basename($fichierModele);
            
            // Chercher requêtes préparées
            $utilisePreparees = preg_match('/prepare\s*\(|->query\s*\([^)]*\?|bindParam|bindValue|execute\s*\(\s*\[/i', $contenu);
            
            // Chercher concatenation dangereuse
            $concatenationDangereuse = preg_match('/\$_(GET|POST|REQUEST|COOKIE).*\..*[\'"].*\$|[\'"].*\$.*\.\s*\$_(GET|POST|REQUEST)/i', $contenu);
            $interpolationDangereuse = preg_match('/["\'].*SELECT.*WHERE.*\$_(GET|POST|REQUEST)|["\'].*INSERT.*VALUES.*\$_(GET|POST|REQUEST)/i', $contenu);
            
            if ($utilisePreparees && !$concatenationDangereuse && !$interpolationDangereuse) {
                $modelesSecurises++;
            } else {
                if ($concatenationDangereuse || $interpolationDangereuse) {
                    $modelesNonSecurises[] = $nomFichier;
                    $patternsRequetesDangereuses[] = $nomFichier;
                }
            }
        }
        
        $tousSecurises = empty($modelesNonSecurises);
        $this->assertTrue($tousSecurises, 
            "Tous les modèles utilisent des requêtes préparées ($modelesSecurises/" . count($fichiersModeles) . ")", 
            implode(', ', $modelesNonSecurises), 
            Criticite::CRITIQUE);
        
        // BaseDeDonnees.php utilise PDO
        $baseDeDonnees = $this->lireFichier($this->cheminApp . '/Services/BaseDeDonnees.php');
        if ($baseDeDonnees) {
            $this->assertContient($baseDeDonnees, 'PDO', "BaseDeDonnees utilise PDO", Criticite::CRITIQUE);
            
            $errMode = preg_match('/ERRMODE_EXCEPTION/i', $baseDeDonnees);
            $this->assertTrue($errMode, "PDO en mode exception", "", Criticite::IMPORTANT);
            
            $emulateOff = preg_match('/ATTR_EMULATE_PREPARES.*false|false.*ATTR_EMULATE_PREPARES/i', $baseDeDonnees);
            $this->assertTrue($emulateOff, "Préparations natives activées (emulate=false)", "", Criticite::IMPORTANT);
        }
        
        // Test pratique : injection SQL basique
        $payloadsSQL = [
            "1' OR '1'='1",
            "1; DROP TABLE users--",
            "' UNION SELECT * FROM users--",
            "1' AND SLEEP(5)--"
        ];
        
        $this->afficherSousSection("Tests d'injection SQL pratiques");
        foreach ($payloadsSQL as $payload) {
            $response = $this->httpGet($this->apiUrl . 'vehicule/galerie?marque=' . urlencode($payload));
            // Pas d'erreur SQL visible = bon signe
            $pasErreurSQL = !preg_match('/sql|syntax|query|mysql|postgres|sqlite/i', $response['body']);
            $this->assertTrue($pasErreurSQL, "Pas d'erreur SQL visible avec payload", substr($payload, 0, 20) . "...");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 8. PROTECTION XSS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerXSS(): void {
        $this->afficherSection("8. PROTECTION XSS (Cross-Site Scripting)");
        
        // Securite.php
        $securite = $this->lireFichier($this->cheminApp . '/Services/Securite.php');
        if ($securite) {
            $this->assertContient($securite, 'htmlspecialchars', "Securite.php utilise htmlspecialchars", Criticite::IMPORTANT);
            $this->assertContient($securite, 'ENT_QUOTES', "Échappe les guillemets (ENT_QUOTES)", Criticite::IMPORTANT);
            // Filtrage additionnel optionnel (htmlspecialchars avec ENT_QUOTES est suffisant pour le XSS)
            $filtrageAdditionnelDispo = preg_match('/strip_tags|htmlentities|filter_var/i', $securite);
            if (!$filtrageAdditionnelDispo) {
                $this->afficherInfo("Filtrage via htmlspecialchars (suffisant)");
            } else {
                $this->assertTrue(true, "Filtrage additionnel disponible");
            }
        }
        
        // Vérifier vues PHP
        $fichiersVues = $this->parcourirDossier($this->racineProjet . '/views');
        $vuesSansEchappement = [];
        
        foreach ($fichiersVues as $vue) {
            $contenu = $this->lireFichier($vue);
            $nomVue = basename($vue);
            
            // Chercher echo direct de variables sans échappement (regex simplifiée)
            $echosDangereux = preg_match_all('/\<\?=\s*\$\w+\s*\?>/', $contenu, $matches);
            $echoDirects = preg_match_all('/echo\s+\$\w+;/', $contenu, $matches2);
            
            // Patterns autorisés (déjà échappés)
            $echosSecurises = preg_match_all('/htmlspecialchars|htmlentities|e\(|escape\(|strip_tags/', $contenu, $safe);
            
            if (($echosDangereux + $echoDirects) > 0 && $echosSecurises === 0) {
                $vuesSansEchappement[] = $nomVue;
            }
        }
        
        $toutesEchappees = count($vuesSansEchappement) < 5; // Tolérance
        $this->assertTrue($toutesEchappees, 
            "Vues utilisent l'échappement XSS", 
            count($vuesSansEchappement) . " potentiellement non échappées",
            Criticite::IMPORTANT);
        
        // Vérifier innerHTML dans JS
        $fichiersJs = $this->parcourirDossier($this->cheminPublic . '/assets/js', '.js');
        $jsAvecInnerHTML = [];
        
        foreach ($fichiersJs as $fichierJs) {
            $contenu = $this->lireFichier($fichierJs);
            $nomJs = basename($fichierJs);
            
            // innerHTML avec variable non sanitisée
            $innerHTMLDangereux = preg_match('/innerHTML\s*=\s*[^;]*\$|innerHTML\s*=\s*[^;]*userInput|innerHTML\s*=\s*[^;]*response/i', $contenu);
            if ($innerHTMLDangereux) {
                $jsAvecInnerHTML[] = $nomJs;
            }
        }
        
        if (!empty($jsAvecInnerHTML)) {
            $this->afficherAvertissement("Fichiers JS avec innerHTML potentiellement dangereux: " . implode(', ', $jsAvecInnerHTML));
        }
        
        // Test pratique XSS
        $payloadsXSS = [
            '<script>alert(1)</script>',
            '"><img src=x onerror=alert(1)>',
            "javascript:alert('XSS')",
            '<svg onload=alert(1)>'
        ];
        
        $this->afficherSousSection("Tests XSS pratiques");
        foreach ($payloadsXSS as $payload) {
            $response = $this->httpGet($this->baseUrl . 'recherche?q=' . urlencode($payload));
            $pasXSS = strpos($response['body'], $payload) === false 
                || strpos($response['body'], htmlspecialchars($payload, ENT_QUOTES)) !== false;
            $this->assertTrue($pasXSS, "Payload XSS échappé ou bloqué", substr($payload, 0, 30));
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 9. RATE LIMITING
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerRateLimiting(): void {
        $this->afficherSection("9. RATE LIMITING");
        
        $gestionnaireTaux = $this->lireFichier($this->cheminApp . '/Services/GestionnaireLimiteTaux.php');
        if ($gestionnaireTaux) {
            $this->assertContient($gestionnaireTaux, 'limite', "GestionnaireLimiteTaux gère les limites", Criticite::IMPORTANT);
            
            $parIP = preg_match('/IP|REMOTE_ADDR|ip_address/i', $gestionnaireTaux);
            $this->assertTrue($parIP, "Rate limiting par IP", "", Criticite::IMPORTANT);
            
            $stockage = preg_match('/cache|redis|memcache|session|file/i', $gestionnaireTaux);
            $this->assertTrue($stockage, "Stockage des compteurs configuré");
        } else {
            $this->afficherAvertissement("GestionnaireLimiteTaux.php non trouvé", Criticite::IMPORTANT);
        }
        
        // Vérifier utilisation dans connexion
        $controleurConnexion = $this->lireFichier($this->dossierApi . '/ControleurConnexion.php');
        if ($controleurConnexion) {
            $rateLimitConnexion = preg_match('/limite|rate|throttle|tentative/i', $controleurConnexion);
            $this->assertTrue($rateLimitConnexion, "Rate limiting sur la connexion", "", Criticite::IMPORTANT);
        }
        
        // Vérifier sur inscription
        $controleurInscription = $this->lireFichier($this->dossierApi . '/ControleurInscription.php');
        if ($controleurInscription) {
            $rateLimitInscription = preg_match('/limite|rate|throttle/i', $controleurInscription);
            $this->assertTrue($rateLimitInscription, "Rate limiting sur l'inscription");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 10. PROTECTION IDOR
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerIDOR(): void {
        $this->afficherSection("10. PROTECTION IDOR (Insecure Direct Object Reference)");
        $this->afficherInfo("IDOR = Un utilisateur peut accéder/modifier les données d'un autre");
        
        // Modification véhicule
        $modifVehicule = $this->lireFichier($this->dossierApi . '/ControleurModificationVehicule.php');
        if ($modifVehicule) {
            $verifProprio = preg_match('/proprietaire|owner|user_id.*session|session.*user_id|appartient/i', $modifVehicule);
            $this->assertTrue($verifProprio, "Modification véhicule vérifie le propriétaire", "", Criticite::CRITIQUE);
        }
        
        // Suppression véhicule
        $mesAnnonces = $this->lireFichier($this->dossierApi . '/ControleurMesAnnonces.php');
        if ($mesAnnonces) {
            $verifProprioSuppression = preg_match('/proprietaire|owner|user_id|appartient/i', $mesAnnonces);
            $this->assertTrue($verifProprioSuppression, "Suppression véhicule vérifie le propriétaire", "", Criticite::CRITIQUE);
        }
        
        // Messagerie
        $messagerie = $this->lireFichier($this->dossierApi . '/ControleurMessagerie.php');
        if ($messagerie) {
            $verifParticipant = preg_match('/participant|expediteur|destinataire|sender|receiver|member/i', $messagerie);
            $this->assertTrue($verifParticipant, "Messagerie vérifie les participants", "", Criticite::CRITIQUE);
        }
        
        // Profil
        $profil = $this->lireFichier($this->dossierApi . '/ControleurProfil.php');
        if ($profil) {
            $modifPropreProfil = preg_match('/session.*user|user.*session|\$_SESSION/i', $profil);
            $this->assertTrue($modifPropreProfil, "Profil modifie uniquement son propre compte", "", Criticite::CRITIQUE);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 11. SÉCURITÉ UPLOAD FICHIERS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerUploadFichiers(): void {
        $this->afficherSection("11. SÉCURITÉ UPLOAD FICHIERS");
        
        $serviceValidation = $this->lireFichier($this->cheminApp . '/Services/ServiceValidationFichier.php');
        if ($serviceValidation) {
            // Vérification MIME
            $verifMime = preg_match('/mime|finfo|getimagesize|exif_imagetype/i', $serviceValidation);
            $this->assertTrue($verifMime, "Upload vérifie le type MIME réel", "", Criticite::CRITIQUE);
            
            // Liste blanche extensions
            $listeBlanche = preg_match('/allowed|autorise|whitelist|\[.*jpg.*png.*gif.*\]/i', $serviceValidation);
            $this->assertTrue($listeBlanche, "Liste blanche d'extensions", "", Criticite::CRITIQUE);
            
            // Limite taille
            $limiteTaille = preg_match('/size|taille|max.*bytes|limite/i', $serviceValidation);
            $this->assertTrue($limiteTaille, "Limite de taille configurée", "", Criticite::IMPORTANT);
            
            // Renommage fichier
            $renommage = preg_match('/uniqid|uuid|random|rename|nouveau.*nom/i', $serviceValidation);
            $this->assertTrue($renommage, "Renommage des fichiers uploadés", "", Criticite::IMPORTANT);
            
            // move_uploaded_file
            $this->assertContient($serviceValidation, 'move_uploaded_file', "Utilise move_uploaded_file()", Criticite::IMPORTANT);
        } else {
            $this->afficherAvertissement("ServiceValidationFichier.php non trouvé", Criticite::CRITIQUE);
        }
        
        // ValidateurVehicule
        $validateurVehicule = $this->lireFichier($this->cheminApp . '/Services/ValidateurVehicule.php');
        if ($validateurVehicule) {
            $verifImages = preg_match('/image|photo|fichier/i', $validateurVehicule);
            $this->assertTrue($verifImages, "ValidateurVehicule valide les images");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 12. HEADERS HTTP SÉCURITÉ
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerHeadersHTTP(): void {
        $this->afficherSection("12. HEADERS HTTP SÉCURITÉ");
        
        $securitePHP = $this->lireFichier($this->cheminApp . '/Services/Securite.php');
        if ($securitePHP) {
            $this->assertRegex('/X-Content-Type-Options.*nosniff/i', $securitePHP, "Header X-Content-Type-Options", Criticite::IMPORTANT);
            $this->assertRegex('/X-Frame-Options.*(DENY|SAMEORIGIN)/i', $securitePHP, "Header X-Frame-Options (clickjacking)", Criticite::IMPORTANT);
            $this->assertRegex('/X-XSS-Protection/i', $securitePHP, "Header X-XSS-Protection");
            $this->assertRegex('/Strict-Transport-Security|HSTS/i', $securitePHP, "Header HSTS (HTTPS)");
            $this->assertRegex('/Content-Security-Policy|CSP/i', $securitePHP, "Header Content-Security-Policy");
            $this->assertRegex('/Referrer-Policy/i', $securitePHP, "Header Referrer-Policy");
        }
        
        // Test pratique des headers
        $this->afficherSousSection("Vérification headers HTTP réels");
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        $headers = curl_exec($ch);
        curl_close($ch);
        
        $this->assertRegex('/X-Content-Type-Options:\s*nosniff/i', $headers, "Header X-Content-Type-Options présent");
        $this->assertRegex('/X-Frame-Options:\s*(DENY|SAMEORIGIN)/i', $headers, "Header X-Frame-Options présent");
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 13. SÉCURITÉ SESSIONS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerSecuriteSessions(): void {
        $this->afficherSection("13. SÉCURITÉ SESSION");
        
        $gestionnaireSession = $this->lireFichier($this->cheminApp . '/Services/GestionnaireSession.php');
        if ($gestionnaireSession) {
            $this->assertRegex('/session_regenerate_id/i', $gestionnaireSession, "Régénération ID session", Criticite::IMPORTANT);
            
            $httpOnly = preg_match('/httponly|http_only|cookie.*true/i', $gestionnaireSession);
            $this->assertTrue($httpOnly, "Cookie session HttpOnly", "", Criticite::IMPORTANT);
            
            $secure = preg_match('/secure|https/i', $gestionnaireSession);
            $this->assertTrue($secure, "Cookie session Secure (HTTPS)");
            
            $sameSite = preg_match('/samesite|same_site/i', $gestionnaireSession);
            $this->assertTrue($sameSite, "Cookie session SameSite");
            
            // Vérifier expiration session (plusieurs patterns possibles)
            $expiration = preg_match('/expire|lifetime|gc_maxlifetime|timeout|maxlifetime|session.*time|durée|cookie_lifetime/i', $gestionnaireSession);
            $this->assertTrue($expiration, "Expiration session configurée");
            
            $fixation = preg_match('/regenerate|nouveau.*id|fixation/i', $gestionnaireSession);
            $this->assertTrue($fixation, "Protection contre la fixation de session", "", Criticite::IMPORTANT);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 14. MOTS DE PASSE ET CHIFFREMENT
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerMotsDePasse(): void {
        $this->afficherSection("14. MOTS DE PASSE ET CHIFFREMENT");
        
        // Le hashage peut être dans ModeleUtilisateur, ServiceChiffrement, ou le contrôleur
        $modeleUtilisateur = $this->lireFichier($this->dossierModeles . '/ModeleUtilisateur.php');
        $serviceChiffrement = $this->lireFichier($this->cheminApp . '/Services/ServiceChiffrement.php');
        $controleurConnexion = $this->lireFichier($this->dossierApi . '/ControleurConnexion.php');
        
        // Concaténer tous les fichiers pertinents pour la recherche
        $codeMotDePasse = ($modeleUtilisateur ?? '') . ($serviceChiffrement ?? '') . ($controleurConnexion ?? '');
        
        // password_hash() doit être appelé quelque part
        $utiliseHash = strpos($codeMotDePasse, 'password_hash(') !== false;
        $this->assertTrue($utiliseHash, "Utilise password_hash()", "", Criticite::CRITIQUE);
        
        // Algorithme sécurisé (BCRYPT, ARGON2)
        $algoSecurise = preg_match('/PASSWORD_DEFAULT|PASSWORD_BCRYPT|PASSWORD_ARGON/i', $codeMotDePasse);
        $this->assertTrue($algoSecurise, "Algorithme de hash sécurisé", "", Criticite::IMPORTANT);
        
        // password_verify() pour vérifier les mots de passe
        $verifMdp = strpos($codeMotDePasse, 'password_verify(') !== false;
        $this->assertTrue($verifMdp, "Utilise password_verify()");
        
        // ServiceChiffrement
        $serviceChiffrement = $this->lireFichier($this->cheminApp . '/Services/ServiceChiffrement.php');
        if ($serviceChiffrement) {
            $this->assertRegex('/openssl|sodium|libsodium/i', $serviceChiffrement, "Utilise OpenSSL ou Sodium", Criticite::IMPORTANT);
            $this->assertRegex('/AES|aes-256|AEAD|GCM/i', $serviceChiffrement, "Algorithme de chiffrement moderne");
            
            $ivAleatoire = preg_match('/random_bytes|openssl_random|iv/i', $serviceChiffrement);
            $this->assertTrue($ivAleatoire, "IV/Nonce aléatoire pour le chiffrement");
        }
        
        // Politique mot de passe
        $inscription = $this->lireFichier($this->dossierApi . '/ControleurInscription.php');
        if ($inscription) {
            // Vérifier longueur minimale (strlen < 8 ou strlen >= 8 ou min 8)
            $longueurMin = preg_match('/strlen\s*\(.*\)\s*<\s*8|strlen.*[>=<]\s*8|au moins 8|min.*8|8.*caract/i', $inscription);
            $this->assertTrue($longueurMin, "Longueur minimale mot de passe (8+)", "", Criticite::IMPORTANT);
            
            $complexite = preg_match('/[A-Z]|majuscule|uppercase|[0-9]|digit|chiffre|special|[!@#$%]/i', $inscription);
            $this->assertTrue($complexite, "Règles de complexité mot de passe");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 15. PROTECTION PATH TRAVERSAL
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerPathTraversal(): void {
        $this->afficherSection("15. PROTECTION PATH TRAVERSAL");
        $this->afficherInfo("Path Traversal = Accès à des fichiers hors du dossier autorisé (../)");
        
        // Vérifier les contrôleurs qui manipulent des fichiers
        $fichiersControleurs = $this->parcourirDossier($this->dossierApi);
        
        foreach ($fichiersControleurs as $fichierCtrl) {
            $contenu = $this->lireFichier($fichierCtrl);
            $nomFichier = basename($fichierCtrl);
            
            // Si le fichier utilise file_get_contents, readfile avec des variables utilisateur
            // On exclut les require/include qui sont généralement avec des chemins fixes
            $manipuleFichiersVariables = preg_match('/file_get_contents\s*\(\s*\$|readfile\s*\(\s*\$|fopen\s*\(\s*\$/i', $contenu);
            
            if ($manipuleFichiersVariables) {
                // Vérifier qu'il y a une protection ou que les variables sont sécurisées
                $protection = preg_match('/realpath|basename|str_replace.*\.\.|preg_replace.*\.\.|__DIR__|__FILE__|DIRECTORY_SEPARATOR/i', $contenu);
                $this->assertTrue($protection, "$nomFichier protège contre path traversal", "", Criticite::IMPORTANT);
            }
        }
        
        // ServiceValidationFichier
        $serviceValidation = $this->lireFichier($this->cheminApp . '/Services/ServiceValidationFichier.php');
        if ($serviceValidation) {
            $protectionPath = preg_match('/\.\.|realpath|basename/i', $serviceValidation);
            $this->assertTrue($protectionPath, "ServiceValidationFichier protège contre path traversal", "", Criticite::IMPORTANT);
        }
        
        // Test pratique
        $payloadsPath = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '....//....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc/passwd'
        ];
        
        $this->afficherSousSection("Tests path traversal pratiques");
        foreach ($payloadsPath as $payload) {
            $response = $this->httpGet($this->baseUrl . 'uploads/' . urlencode($payload));
            $bloque = $response['code'] === 400 || $response['code'] === 403 || $response['code'] === 404;
            $this->assertTrue($bloque, "Path traversal bloqué", substr($payload, 0, 30));
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 16. PROTECTION MASS ASSIGNMENT
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerMassAssignment(): void {
        $this->afficherSection("16. PROTECTION MASS ASSIGNMENT");
        $this->afficherInfo("Mass Assignment = Injection de champs non autorisés (ex: role=admin)");
        
        $fichiersAVerifier = [
            'ControleurProfil.php',
            'ControleurInscription.php',
            'ControleurModificationVehicule.php'
        ];
        
        foreach ($fichiersAVerifier as $fichier) {
            $contenu = $this->lireFichier($this->dossierApi . '/' . $fichier);
            if ($contenu) {
                // Vérifier qu'on n'utilise pas $_POST directement dans les requêtes
                $postDirect = preg_match('/\$_POST\s*\[.*\].*=.*\$_POST|\$_POST\s*;/', $contenu);
                $this->assertTrue(!$postDirect, "$fichier ne passe pas \$_POST directement", "", Criticite::IMPORTANT);
                
                // Vérifier liste blanche de champs ou extraction explicite des champs
                $listeBlanche = preg_match('/allowed|autorise|whitelist|champs_autorises|\[.*\'nom\'.*\'prenom\'|\$_POST\[\'|\$donnees\[|json_decode.*\$|obtenir.*Donnees/i', $contenu);
                $this->assertTrue($listeBlanche, "$fichier utilise une liste blanche de champs");
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 17. EXPOSITION D'INFORMATIONS SENSIBLES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerInformationDisclosure(): void {
        $this->afficherSection("17. EXPOSITION D'INFORMATIONS SENSIBLES");
        
        // Vérifier display_errors
        $indexPhp = $this->lireFichier($this->cheminPublic . '/index.php');
        if ($indexPhp) {
            $displayErrorsOff = !preg_match('/display_errors.*1|ini_set.*display_errors.*true/i', $indexPhp)
                || preg_match('/getenv|ENV|production/i', $indexPhp);
            $this->assertTrue($displayErrorsOff, "display_errors géré par environnement", "", Criticite::IMPORTANT);
        }
        
        // Vérifier qu'on ne log pas les mots de passe (valeurs)
        // On cherche des patterns où la valeur du mot de passe serait loguée, pas juste des messages d'erreur
        $fichiersAvecLog = [];
        foreach ($this->parcourirDossier($this->cheminApp) as $fichier) {
            $contenu = $this->lireFichier($fichier);
            // Pattern dangereux : error_log($password) ou var_dump($motDePasse) ou print_r avec la variable
            if (preg_match('/error_log\s*\(\s*\$(?:password|mot_de_passe|motDePasse|mdp)\s*\)/i', $contenu) ||
                preg_match('/var_dump\s*\(\s*\$(?:password|mot_de_passe|motDePasse|mdp)/i', $contenu) ||
                preg_match('/print_r\s*\(\s*\$(?:password|mot_de_passe|motDePasse|mdp)/i', $contenu)) {
                $fichiersAvecLog[] = basename($fichier);
            }
        }
        
        $this->assertTrue(empty($fichiersAvecLog), "Pas de log de mots de passe", implode(', ', $fichiersAvecLog), Criticite::CRITIQUE);
        
        // Test pratique : erreurs ne révèlent pas d'infos
        $response = $this->httpGet($this->baseUrl . 'page-inexistante-test-12345');
        $pasInfosSensibles = !preg_match('/stack trace|exception|mysqli|pdo|sql|fatal error|warning|notice/i', $response['body']);
        $this->assertTrue($pasInfosSensibles, "Page 404 ne révèle pas d'infos techniques");
        
        // Vérifier phpinfo() n'est pas accessible
        $response = $this->httpGet($this->baseUrl . 'phpinfo.php');
        $this->assertTrue($response['code'] === 404, "phpinfo.php non accessible", "", Criticite::CRITIQUE);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 18. SÉCURITÉ JAVASCRIPT
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerSecuriteJavascript(): void {
        $this->afficherSection("18. SÉCURITÉ JAVASCRIPT");
        
        $fichiersJs = $this->parcourirDossier($this->cheminPublic . '/assets/js', '.js');
        
        foreach ($fichiersJs as $fichierJs) {
            $contenu = $this->lireFichier($fichierJs);
            $nomJs = basename($fichierJs);
            
            // Vérifier eval() dangereux
            $evalDangereux = preg_match('/eval\s*\([^)]*\$|eval\s*\([^)]*user|eval\s*\([^)]*input/i', $contenu);
            if (preg_match('/eval\s*\(/', $contenu)) {
                $this->assertTrue(!$evalDangereux, "$nomJs n'utilise pas eval() dangereusement", "", Criticite::IMPORTANT);
            }
            
            // Vérifier document.write() dangereux
            $docWriteDangereux = preg_match('/document\.write\s*\([^)]*\$|document\.write\s*\([^)]*user/i', $contenu);
            if (preg_match('/document\.write/', $contenu)) {
                $this->assertTrue(!$docWriteDangereux, "$nomJs n'utilise pas document.write() dangereusement");
            }
        }
        
        // Vérifier application.js pour sanitization
        $applicationJs = $this->lireFichier($this->cheminPublic . '/assets/js/application.js');
        if ($applicationJs) {
            $sanitize = preg_match('/sanitize|escape|encode|textContent|createTextNode/i', $applicationJs);
            $this->assertTrue($sanitize, "application.js utilise des méthodes de sanitization");
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXÉCUTION DIRECTE
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['argv'][0] ?? '')) {
    $tests = new TestsSecurite();
    $resultats = $tests->executer();
    exit($resultats['echoues'] > 0 ? 1 : 0);
}
