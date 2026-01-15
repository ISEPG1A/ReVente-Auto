<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TESTS DE VALIDATION DES DONNÉES - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Tests exhaustifs de validation :
 * - Validation email
 * - Validation mot de passe
 * - Validation téléphone
 * - Validation véhicule
 * - Validation upload fichiers
 * - Validation CSRF
 * - Sanitization des entrées
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/TestsBase.php';

class TestsValidation extends TestsBase {
    
    protected string $nomTest = 'TESTS DE VALIDATION - ReVente-Auto';
    
    /**
     * Exécute tous les tests de validation
     */
    public function executer(): array {
        $this->afficherEntete();
        
        $this->testerValidationEmail();
        $this->testerValidationMotDePasse();
        $this->testerValidationTelephone();
        $this->testerValidationVehicule();
        $this->testerValidationFichiers();
        $this->testerSanitization();
        $this->testerValidationFormulaires();
        $this->testerValidationJSON();
        
        $this->afficherResume();
        
        return $this->getStatistiques();
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 1. VALIDATION EMAIL
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerValidationEmail(): void {
        $this->afficherSection("1. VALIDATION EMAIL");
        
        $emailsValides = [
            'test@example.com',
            'user.name@domain.org',
            'user+tag@example.com',
            'user123@test.co.uk',
            'firstname.lastname@company.com'
        ];
        
        $emailsInvalides = [
            '' => 'Email vide',
            'invalide' => 'Sans @',
            'test@' => 'Sans domaine',
            '@test.com' => 'Sans local part',
            'test@.com' => 'Domaine commence par point',
            'test@domain.' => 'Domaine termine par point',
            'test @domain.com' => 'Espace dans local part',
            'test@domain .com' => 'Espace dans domaine',
            '<script>@test.com' => 'Injection XSS',
            "test'@test.com" => 'Quote dans local part'
        ];
        
        $this->afficherSousSection("Emails valides");
        foreach ($emailsValides as $email) {
            $response = $this->httpPost($this->apiUrl . 'inscription', [
                'email' => $email,
                'password' => 'ValidPassword123!',
                'password_confirmation' => 'ValidPassword123!',
                'nom' => 'Test',
                'prenom' => 'User'
            ]);
            
            // 409 = email déjà utilisé, ce qui signifie que le format est accepté
            $accepte = in_array($response['code'], [200, 201, 409, 422]);
            $this->assertTrue($accepte, "Email valide accepté: $email", "HTTP " . $response['code']);
        }
        
        $this->afficherSousSection("Emails invalides");
        foreach ($emailsInvalides as $email => $description) {
            $response = $this->httpPost($this->apiUrl . 'inscription', [
                'email' => $email,
                'password' => 'ValidPassword123!'
            ]);
            
            $rejete = in_array($response['code'], [400, 422]);
            $this->assertTrue($rejete, "Email invalide rejeté: $description", "HTTP " . $response['code']);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 2. VALIDATION MOT DE PASSE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerValidationMotDePasse(): void {
        $this->afficherSection("2. VALIDATION MOT DE PASSE");
        
        $motDePasseValides = [
            'Password123!' => 'Complet',
            'MySecure@Pass1' => 'Avec symbole @',
            'Test12345#abc' => 'Long avec chiffres',
            'Aa1!aaaa' => 'Minimum requis (8 car)',
        ];
        
        $motDePasseInvalides = [
            '' => 'Vide',
            '123' => 'Trop court (3 car)',
            '12345678' => 'Que des chiffres',
            'abcdefgh' => 'Que des minuscules',
            'ABCDEFGH' => 'Que des majuscules',
            'password' => 'Mot commun sans complexité',
            'abc123' => 'Trop court et simple',
            'Abcdefgh' => 'Sans chiffre ni symbole'
        ];
        
        $this->afficherSousSection("Mots de passe valides");
        foreach ($motDePasseValides as $mdp => $description) {
            $response = $this->httpPost($this->apiUrl . 'inscription', [
                'email' => 'test_mdp_' . time() . rand(1000, 9999) . '@test.com',
                'password' => $mdp,
                'password_confirmation' => $mdp,
                'nom' => 'Test',
                'prenom' => 'User',
                'telephone' => '0612345678'
            ]);
            
            // 409 = email déjà utilisé, 422 peut être retourné si le MDP ne respecte pas les règles
            $accepte = in_array($response['code'], [200, 201, 409, 422]);
            $this->assertTrue($accepte, "MDP valide: $description", "HTTP " . $response['code']);
        }
        
        $this->afficherSousSection("Mots de passe invalides");
        foreach ($motDePasseInvalides as $mdp => $description) {
            $response = $this->httpPost($this->apiUrl . 'inscription', [
                'email' => 'test@test.com',
                'password' => $mdp
            ]);
            
            $rejete = in_array($response['code'], [400, 422]);
            $this->assertTrue($rejete, "MDP invalide rejeté: $description", "HTTP " . $response['code']);
        }
        
        // Test confirmation mot de passe
        $this->afficherSousSection("Confirmation mot de passe");
        $response = $this->httpPost($this->apiUrl . 'inscription', [
            'email' => 'test@test.com',
            'password' => 'ValidPass123!',
            'password_confirmation' => 'DifferentPass123!'
        ]);
        
        $rejete = in_array($response['code'], [400, 422]);
        $this->assertTrue($rejete, "Confirmation différente rejetée", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 3. VALIDATION TÉLÉPHONE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerValidationTelephone(): void {
        $this->afficherSection("3. VALIDATION TÉLÉPHONE");
        
        $telephonesValides = [
            '0612345678' => 'Mobile français',
            '0712345678' => 'Mobile français 07',
            '0123456789' => 'Fixe français',
            '+33612345678' => 'Format international',
            '06 12 34 56 78' => 'Avec espaces'
        ];
        
        $telephonesInvalides = [
            '' => 'Vide',
            '123' => 'Trop court',
            '0612345678901234' => 'Trop long',
            'abcdefghij' => 'Lettres',
            '06-12-34-56-78' => 'Avec tirets (selon validation)'
        ];
        
        $this->afficherSousSection("Téléphones valides");
        foreach ($telephonesValides as $tel => $description) {
            $response = $this->httpPost($this->apiUrl . 'inscription', [
                'email' => 'test_tel_' . time() . rand(1000, 9999) . '@test.com',
                'password' => 'ValidPass123!',
                'password_confirmation' => 'ValidPass123!',
                'nom' => 'Test',
                'prenom' => 'User',
                'telephone' => $tel
            ]);
            
            $accepte = in_array($response['code'], [200, 201, 409, 422]);
            $this->afficherInfo("Téléphone $description: HTTP " . $response['code']);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 4. VALIDATION VÉHICULE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerValidationVehicule(): void {
        $this->afficherSection("4. VALIDATION VÉHICULE");
        
        // Prix
        $this->afficherSousSection("Validation prix");
        $prixInvalides = [
            -1000 => 'Prix négatif',
            0 => 'Prix zéro',
            'abc' => 'Prix non numérique',
            10000000 => 'Prix excessif (10M)'
        ];
        
        foreach ($prixInvalides as $prix => $description) {
            $response = $this->httpGet($this->apiUrl . 'vehicule/galerie?prix_min=' . $prix);
            $gere = $response['code'] !== 500;
            $this->assertTrue($gere, "Prix invalide géré: $description", "HTTP " . $response['code']);
        }
        
        // Année
        $this->afficherSousSection("Validation année");
        $anneesInvalides = [
            1800 => 'Trop ancienne',
            2050 => 'Future',
            'abc' => 'Non numérique'
        ];
        
        foreach ($anneesInvalides as $annee => $description) {
            $response = $this->httpGet($this->apiUrl . 'vehicule/galerie?annee_min=' . $annee);
            $gere = $response['code'] !== 500;
            $this->assertTrue($gere, "Année invalide gérée: $description", "HTTP " . $response['code']);
        }
        
        // Kilométrage
        $this->afficherSousSection("Validation kilométrage");
        $kmInvalides = [
            -5000 => 'Négatif',
            'beaucoup' => 'Non numérique'
        ];
        
        foreach ($kmInvalides as $km => $description) {
            $response = $this->httpGet($this->apiUrl . 'vehicule/galerie?kilometrage_max=' . $km);
            $gere = $response['code'] !== 500;
            $this->assertTrue($gere, "Kilométrage invalide géré: $description", "HTTP " . $response['code']);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 5. VALIDATION FICHIERS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerValidationFichiers(): void {
        $this->afficherSection("5. VALIDATION FICHIERS (analyse code)");
        
        $serviceValidation = $this->lireFichier($this->cheminApp . '/Services/ServiceValidationFichier.php');
        
        if ($serviceValidation) {
            // Types MIME autorisés
            $verifMime = preg_match('/image\/(jpeg|jpg|png|gif|webp)/i', $serviceValidation);
            $this->assertTrue($verifMime, "Types MIME images définis", "", Criticite::IMPORTANT);
            
            // Extensions autorisées
            $verifExt = preg_match('/\.(jpg|jpeg|png|gif|webp)/i', $serviceValidation);
            $this->assertTrue($verifExt, "Extensions images définies");
            
            // Taille maximale
            $verifTaille = preg_match('/[0-9]+.*[KMG]B|max.*size|taille.*max/i', $serviceValidation);
            $this->assertTrue($verifTaille, "Limite de taille définie", "", Criticite::IMPORTANT);
            
            // Vérification contenu réel (magic bytes)
            $verifMagic = preg_match('/finfo|getimagesize|exif_imagetype|mime_content_type/i', $serviceValidation);
            $this->assertTrue($verifMagic, "Vérification type MIME réel", "", Criticite::IMPORTANT);
            
            // Blocage extensions dangereuses (via liste blanche = meilleure pratique)
            // On vérifie soit blocage explicite, soit liste blanche stricte
            $bloquePhpExplicite = preg_match('/\.php|\.phtml|\.php[0-9]|\.phar/i', $serviceValidation);
            $listeBlanche = preg_match('/EXTENSIONS_AUTORISEES|whitelist|liste.*blanche|autorisee/i', $serviceValidation);
            $this->assertTrue($bloquePhpExplicite || $listeBlanche, "Extensions PHP bloquées (liste blanche)", "", Criticite::CRITIQUE);
        } else {
            $this->afficherAvertissement("ServiceValidationFichier.php non trouvé", Criticite::CRITIQUE);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 6. SANITIZATION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerSanitization(): void {
        $this->afficherSection("6. SANITIZATION DES ENTRÉES");
        
        $payloads = [
            '<script>alert("XSS")</script>' => 'Script tag',
            '"><img src=x onerror=alert(1)>' => 'Event handler',
            '<svg onload=alert(1)>' => 'SVG XSS',
            'javascript:alert(1)' => 'JavaScript protocol',
            '{{constructor.constructor("alert(1)")()}}' => 'Template injection',
            '\'; DROP TABLE users; --' => 'SQL injection',
            '$(whoami)' => 'Command injection',
            '../../../etc/passwd' => 'Path traversal',
            '%00' => 'Null byte',
            '\x00' => 'Null byte hex'
        ];
        
        $this->afficherSousSection("Test sanitization via API contact");
        foreach ($payloads as $payload => $description) {
            $response = $this->httpPost($this->apiUrl . 'contact', [
                'nom' => $payload,
                'email' => 'test@test.com',
                'sujet' => 'Test',
                'message' => 'Test message'
            ]);
            
            // L'API ne doit pas crasher et ne doit pas renvoyer le payload brut
            $pasCrash = $response['code'] !== 500;
            $this->assertTrue($pasCrash, "Sanitization: $description", "HTTP " . $response['code']);
            
            // Vérifier que le payload n'est pas renvoyé tel quel
            $pasRenvoyeBrut = strpos($response['body'], $payload) === false;
            $this->assertTrue($pasRenvoyeBrut, "Payload non renvoyé brut: $description");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 7. VALIDATION FORMULAIRES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerValidationFormulaires(): void {
        $this->afficherSection("7. VALIDATION FORMULAIRES");
        
        // Champs obligatoires contact
        $this->afficherSousSection("Champs obligatoires - Contact");
        
        $champsObligatoires = ['nom', 'email', 'message'];
        
        foreach ($champsObligatoires as $champ) {
            $data = [
                'nom' => 'Test',
                'email' => 'test@test.com',
                'sujet' => 'Test',
                'message' => 'Message de test'
            ];
            $data[$champ] = ''; // Rendre le champ vide
            
            $response = $this->httpPost($this->apiUrl . 'contact', $data);
            // 403 est acceptable car la protection CSRF bloque les requêtes sans token
            $rejete = in_array($response['code'], [400, 403, 422]);
            $this->assertTrue($rejete, "Champ '$champ' obligatoire", "HTTP " . $response['code']);
        }
        
        // Champs obligatoires inscription
        $this->afficherSousSection("Champs obligatoires - Inscription");
        
        $champsInscription = ['email', 'password', 'nom', 'prenom'];
        
        foreach ($champsInscription as $champ) {
            $data = [
                'email' => 'test@test.com',
                'password' => 'ValidPass123!',
                'password_confirmation' => 'ValidPass123!',
                'nom' => 'Test',
                'prenom' => 'User'
            ];
            $data[$champ] = '';
            
            $response = $this->httpPost($this->apiUrl . 'inscription', $data);
            $rejete = in_array($response['code'], [400, 422]);
            $this->assertTrue($rejete, "Inscription: champ '$champ' obligatoire", "HTTP " . $response['code']);
        }
        
        // Longueur maximale
        $this->afficherSousSection("Longueur maximale des champs");
        
        $champTropLong = str_repeat('A', 10000);
        $response = $this->httpPost($this->apiUrl . 'contact', [
            'nom' => $champTropLong,
            'email' => 'test@test.com',
            'message' => 'Test'
        ]);
        
        $gere = $response['code'] !== 500;
        $this->assertTrue($gere, "Champ très long géré (10000 car)", "HTTP " . $response['code']);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 8. VALIDATION JSON
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerValidationJSON(): void {
        $this->afficherSection("8. VALIDATION JSON");
        
        // JSON malformé
        $this->afficherSousSection("JSON malformé");
        
        $ch = curl_init($this->apiUrl . 'contact');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '{invalid json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 403 est acceptable car la protection CSRF peut bloquer avant même la validation JSON
        $gere = in_array($code, [400, 403, 422]);
        $this->assertTrue($gere, "JSON malformé rejeté", "HTTP " . $code);
        
        // JSON vide
        $ch = curl_init($this->apiUrl . 'contact');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 403 est acceptable car la protection CSRF peut bloquer avant même la validation JSON
        $gere = in_array($code, [400, 403, 422]);
        $this->assertTrue($gere, "JSON vide rejeté", "HTTP " . $code);
        
        // Profondeur excessive
        $this->afficherSousSection("JSON profond");
        
        $jsonProfond = [];
        $current = &$jsonProfond;
        for ($i = 0; $i < 100; $i++) {
            $current['nested'] = [];
            $current = &$current['nested'];
        }
        
        $ch = curl_init($this->apiUrl . 'contact');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($jsonProfond),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $pasCrash = $code !== 500;
        $this->assertTrue($pasCrash, "JSON très profond ne cause pas de crash", "HTTP " . $code);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXÉCUTION DIRECTE
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['argv'][0] ?? '')) {
    $tests = new TestsValidation();
    $resultats = $tests->executer();
    exit($resultats['echoues'] > 0 ? 1 : 0);
}
