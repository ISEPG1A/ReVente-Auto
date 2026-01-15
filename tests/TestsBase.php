<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CLASSE DE BASE POUR TOUS LES TESTS - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe fournit les utilitaires communs à tous les tests :
 * - Couleurs terminal
 * - Assertions
 * - Compteurs
 * - Utilitaires HTTP
 * - Affichage formaté
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Éviter la double déclaration
if (!class_exists('TestsBase')) {

/**
 * Classe de couleurs pour le terminal
 */
class Couleurs {
    const VERT = "\033[32m";
    const ROUGE = "\033[31m";
    const JAUNE = "\033[33m";
    const BLEU = "\033[34m";
    const CYAN = "\033[36m";
    const MAGENTA = "\033[35m";
    const RESET = "\033[0m";
    const GRAS = "\033[1m";
    const SOULIGNE = "\033[4m";
    const DIM = "\033[2m";
    
    public static function estSupporte(): bool {
        return DIRECTORY_SEPARATOR !== '\\' || getenv('ANSICON') || getenv('ConEmuANSI') === 'ON';
    }
}

/**
 * Niveaux de criticité des tests
 */
class Criticite {
    const CRITIQUE = 'CRITIQUE';
    const IMPORTANT = 'IMPORTANT';
    const MOYEN = 'MOYEN';
    const INFO = 'INFO';
}

/**
 * Classe de base pour tous les tests
 */
abstract class TestsBase {
    
    // Compteurs
    protected int $testsReussis = 0;
    protected int $testsEchoues = 0;
    protected int $avertissements = 0;
    protected int $testsCritiques = 0;
    protected int $testsImportants = 0;
    protected int $totalTests = 0;
    
    // Configuration
    protected string $baseUrl;
    protected string $apiUrl;
    protected string $racineProjet;
    protected string $cheminApp;
    protected string $cheminPublic;
    
    // Session HTTP
    protected ?string $sessionCookie = null;
    protected ?string $csrfToken = null;
    
    // Temps d'exécution
    protected float $tempsDebut;
    protected array $resultats = [];
    
    // Nom du test
    protected string $nomTest = 'Tests';
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->tempsDebut = microtime(true);
        $this->racineProjet = dirname(__DIR__);
        $this->cheminApp = $this->racineProjet . '/app';
        $this->cheminPublic = $this->racineProjet . '/public';
        $this->baseUrl = 'http://localhost/test/ReVente-Auto/';
        $this->apiUrl = $this->baseUrl . 'api/';
    }
    
    /**
     * Méthode principale d'exécution - à implémenter
     */
    abstract public function executer(): array;
    
    // ════════════════════════════════════════════════════════════════════════
    // ASSERTIONS
    // ════════════════════════════════════════════════════════════════════════
    
    /**
     * Vérifie qu'une condition est vraie
     */
    protected function assertTrue(bool $condition, string $message, string $details = '', string $criticite = Criticite::MOYEN): bool {
        $this->totalTests++;
        
        if ($condition) {
            $this->testsReussis++;
            echo Couleurs::VERT . "✓ " . Couleurs::RESET . $message;
            $this->resultats[] = ['test' => $message, 'succes' => true, 'criticite' => $criticite];
        } else {
            $this->testsEchoues++;
            $this->enregistrerEchec($criticite);
            echo $this->formatEchec($criticite) . $message;
            $this->resultats[] = ['test' => $message, 'succes' => false, 'criticite' => $criticite, 'details' => $details];
        }
        
        if ($details) {
            echo " - " . $details;
        }
        echo "\n";
        
        return $condition;
    }
    
    /**
     * Vérifie l'égalité de deux valeurs
     */
    protected function assertEquals($attendu, $obtenu, string $message, string $criticite = Criticite::MOYEN): bool {
        $details = $attendu === $obtenu ? '' : "attendu: $attendu, obtenu: $obtenu";
        return $this->assertTrue($attendu === $obtenu, $message, $details, $criticite);
    }
    
    /**
     * Vérifie qu'une valeur n'est pas nulle
     */
    protected function assertNotNull($valeur, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue($valeur !== null, $message, '', $criticite);
    }
    
    /**
     * Vérifie qu'une valeur est vide
     */
    protected function assertEmpty($valeur, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue(empty($valeur), $message, '', $criticite);
    }
    
    /**
     * Vérifie qu'une valeur n'est pas vide
     */
    protected function assertNotEmpty($valeur, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue(!empty($valeur), $message, '', $criticite);
    }
    
    /**
     * Vérifie qu'une chaîne contient un texte
     */
    protected function assertContient(?string $haystack, string $needle, string $message, string $criticite = Criticite::MOYEN): bool {
        $contient = $haystack !== null && strpos($haystack, $needle) !== false;
        return $this->assertTrue($contient, $message, '', $criticite);
    }
    
    /**
     * Vérifie qu'une chaîne ne contient pas un texte
     */
    protected function assertNeContientPas(?string $haystack, string $needle, string $message, string $criticite = Criticite::MOYEN): bool {
        $contient = $haystack !== null && strpos($haystack, $needle) !== false;
        return $this->assertTrue(!$contient, $message, '', $criticite);
    }
    
    /**
     * Vérifie un code HTTP
     */
    protected function assertHttpCode(int $attendu, int $obtenu, string $message): bool {
        return $this->assertTrue($obtenu === $attendu, "$message [HTTP $obtenu]", "attendu HTTP $attendu");
    }
    
    /**
     * Vérifie qu'une réponse contient du JSON valide
     */
    protected function assertJsonValide(?string $response, string $message): bool {
        if ($response === null) return $this->assertTrue(false, $message, 'Réponse nulle');
        $json = json_decode($response, true);
        return $this->assertTrue($json !== null && json_last_error() === JSON_ERROR_NONE, $message);
    }
    
    /**
     * Vérifie qu'un fichier existe
     */
    protected function assertFichierExiste(string $chemin, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue(file_exists($chemin), $message, '', $criticite);
    }
    
    /**
     * Vérifie qu'un fichier n'existe pas
     */
    protected function assertFichierNExistePas(string $chemin, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue(!file_exists($chemin), $message, '', $criticite);
    }
    
    /**
     * Vérifie une expression régulière
     */
    protected function assertRegex(string $pattern, ?string $contenu, string $message, string $criticite = Criticite::MOYEN): bool {
        $match = $contenu !== null && preg_match($pattern, $contenu) === 1;
        return $this->assertTrue($match, $message, '', $criticite);
    }
    
    /**
     * Vérifie qu'une valeur est supérieure
     */
    protected function assertSuperieur($valeur, $minimum, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue($valeur > $minimum, $message, "valeur: $valeur, minimum: $minimum", $criticite);
    }
    
    /**
     * Vérifie qu'une valeur est inférieure
     */
    protected function assertInferieur($valeur, $maximum, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue($valeur < $maximum, $message, "valeur: $valeur, maximum: $maximum", $criticite);
    }
    
    /**
     * Vérifie qu'une valeur est un tableau
     */
    protected function assertIsArray($valeur, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue(is_array($valeur), $message, '', $criticite);
    }
    
    /**
     * Vérifie qu'un tableau contient une clé
     */
    protected function assertArrayHasKey(string $cle, $tableau, string $message = '', string $criticite = Criticite::MOYEN): bool {
        $msg = $message ?: "Le tableau devrait contenir la clé '$cle'";
        return $this->assertTrue(is_array($tableau) && array_key_exists($cle, $tableau), $msg, '', $criticite);
    }
    
    /**
     * Vérifie qu'une valeur est supérieure ou égale
     */
    protected function assertGreaterThanOrEqual($minimum, $valeur, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue($valeur >= $minimum, $message, "valeur: $valeur, minimum: $minimum", $criticite);
    }
    
    /**
     * Vérifie qu'une chaîne contient une sous-chaîne (insensible à la casse)
     */
    protected function assertStringContainsString(string $needle, string $haystack, string $message = '', string $criticite = Criticite::MOYEN): bool {
        $msg = $message ?: "La chaîne devrait contenir '$needle'";
        $contient = stripos($haystack, $needle) !== false;
        return $this->assertTrue($contient, $msg, '', $criticite);
    }
    
    /**
     * Vérifie qu'une valeur est NULL
     */
    protected function assertNull($valeur, string $message, string $criticite = Criticite::MOYEN): bool {
        return $this->assertTrue($valeur === null, $message, '', $criticite);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // UTILITAIRES HTTP
    // ════════════════════════════════════════════════════════════════════════
    
    /**
     * Effectue une requête HTTP GET
     */
    protected function httpGet(string $url, bool $suivreRedirections = true): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $suivreRedirections,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_COOKIE => $this->sessionCookie ?? ''
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $temps = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'body' => $response,
            'code' => $httpCode,
            'temps' => $temps,
            'error' => $error
        ];
    }
    
    /**
     * Effectue une requête HTTP POST
     */
    protected function httpPost(string $url, array $data = [], bool $json = true): array {
        $ch = curl_init();
        
        $headers = ['Accept: application/json'];
        
        if ($json) {
            $headers[] = 'Content-Type: application/json';
            $postData = json_encode($data);
        } else {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $postData = http_build_query($data);
        }
        
        if ($this->csrfToken) {
            $headers[] = 'X-CSRF-Token: ' . $this->csrfToken;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIE => $this->sessionCookie ?? '',
            CURLOPT_HEADER => true
        ]);
        
        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $temps = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);
        
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        
        // Extraire les cookies
        if (preg_match_all('/Set-Cookie:\s*([^;]+)/i', $headers, $matches)) {
            $this->sessionCookie = implode('; ', $matches[1]);
        }
        
        curl_close($ch);
        
        return [
            'body' => $body,
            'code' => $httpCode,
            'temps' => $temps,
            'headers' => $headers,
            'error' => $error
        ];
    }
    
    /**
     * Effectue une requête HTTP PUT
     */
    protected function httpPut(string $url, array $data = []): array {
        $ch = curl_init();
        
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];
        
        if ($this->csrfToken) {
            $headers[] = 'X-CSRF-Token: ' . $this->csrfToken;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIE => $this->sessionCookie ?? ''
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $temps = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        return [
            'body' => $response,
            'code' => $httpCode,
            'temps' => $temps
        ];
    }
    
    /**
     * Effectue une requête HTTP DELETE
     */
    protected function httpDelete(string $url): array {
        $ch = curl_init();
        
        $headers = ['Accept: application/json'];
        
        if ($this->csrfToken) {
            $headers[] = 'X-CSRF-Token: ' . $this->csrfToken;
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_COOKIE => $this->sessionCookie ?? ''
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $temps = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        curl_close($ch);
        
        return [
            'body' => $response,
            'code' => $httpCode,
            'temps' => $temps
        ];
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // UTILITAIRES FICHIERS
    // ════════════════════════════════════════════════════════════════════════
    
    /**
     * Lit le contenu d'un fichier
     */
    protected function lireFichier(string $chemin): ?string {
        if (!file_exists($chemin)) {
            return null;
        }
        return file_get_contents($chemin);
    }
    
    /**
     * Parcourt récursivement un dossier
     */
    protected function parcourirDossier(string $dossier, string $extension = '.php'): array {
        $fichiers = [];
        if (!is_dir($dossier)) return $fichiers;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dossier, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $fichier) {
            if ($fichier->isFile() && str_ends_with($fichier->getFilename(), $extension)) {
                $fichiers[] = $fichier->getPathname();
            }
        }
        return $fichiers;
    }
    
    /**
     * Vérifie si un contenu correspond à un pattern regex
     */
    protected function contientRegex(?string $contenu, string $pattern): bool {
        if ($contenu === null) return false;
        return preg_match($pattern, $contenu) === 1;
    }
    
    /**
     * Compte les occurrences d'un pattern regex
     */
    protected function compterRegex(?string $contenu, string $pattern): int {
        if ($contenu === null) return 0;
        preg_match_all($pattern, $contenu, $matches);
        return count($matches[0]);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // AFFICHAGE
    // ════════════════════════════════════════════════════════════════════════
    
    /**
     * Affiche l'en-tête du test
     */
    protected function afficherEntete(): void {
        echo Couleurs::GRAS . "\n";
        echo "╔══════════════════════════════════════════════════════════════════════╗\n";
        echo "║  " . str_pad($this->nomTest, 68) . "║\n";
        echo "║  " . str_pad(date('Y-m-d H:i:s'), 68) . "║\n";
        echo "╚══════════════════════════════════════════════════════════════════════╝\n";
        echo Couleurs::RESET;
    }
    
    /**
     * Affiche une section
     */
    protected function afficherSection(string $titre): void {
        echo "\n" . Couleurs::BLEU . Couleurs::GRAS . "═══ " . $titre . " ═══" . Couleurs::RESET . "\n\n";
    }
    
    /**
     * Affiche une sous-section
     */
    protected function afficherSousSection(string $titre): void {
        echo Couleurs::CYAN . "--- " . $titre . " ---" . Couleurs::RESET . "\n";
    }
    
    /**
     * Affiche une information
     */
    protected function afficherInfo(string $message): void {
        echo Couleurs::CYAN . "ℹ " . Couleurs::RESET . $message . "\n";
    }
    
    /**
     * Affiche un avertissement
     */
    protected function afficherAvertissement(string $message, string $criticite = Criticite::MOYEN): void {
        $this->avertissements++;
        $prefix = $criticite === Criticite::CRITIQUE ? "[CRITIQUE] " : "";
        echo Couleurs::JAUNE . "⚠ AVERTISSEMENT: " . $prefix . Couleurs::RESET . $message . "\n";
    }
    
    /**
     * Affiche le résumé des tests
     */
    protected function afficherResume(): void {
        $tempsFin = microtime(true);
        $duree = round($tempsFin - $this->tempsDebut, 2);
        
        echo "\n";
        echo Couleurs::GRAS . "╔══════════════════════════════════════════════════════════════════════╗\n";
        echo "║                           RÉSUMÉ                                     ║\n";
        echo "╠══════════════════════════════════════════════════════════════════════╣\n" . Couleurs::RESET;
        
        echo "║ " . Couleurs::VERT . "Tests réussis:    " . str_pad($this->testsReussis, 5) . Couleurs::RESET . str_repeat(' ', 47) . "║\n";
        echo "║ " . Couleurs::ROUGE . "Tests échoués:    " . str_pad($this->testsEchoues, 5) . Couleurs::RESET . str_repeat(' ', 47) . "║\n";
        
        if ($this->testsCritiques > 0) {
            echo "║ " . Couleurs::ROUGE . Couleurs::GRAS . "Failles critiques: " . str_pad($this->testsCritiques, 4) . Couleurs::RESET . str_repeat(' ', 47) . "║\n";
        }
        if ($this->testsImportants > 0) {
            echo "║ " . Couleurs::ROUGE . "Failles importantes: " . str_pad($this->testsImportants, 2) . Couleurs::RESET . str_repeat(' ', 47) . "║\n";
        }
        if ($this->avertissements > 0) {
            echo "║ " . Couleurs::JAUNE . "Avertissements:   " . str_pad($this->avertissements, 5) . Couleurs::RESET . str_repeat(' ', 47) . "║\n";
        }
        
        echo "║ Durée d'exécution: " . str_pad($duree . 's', 52) . "║\n";
        echo "╠══════════════════════════════════════════════════════════════════════╣\n";
        
        $total = $this->testsReussis + $this->testsEchoues;
        $pourcentage = $total > 0 ? round(($this->testsReussis / $total) * 100, 1) : 0;
        
        if ($this->testsCritiques > 0) {
            echo "║ " . Couleurs::ROUGE . Couleurs::GRAS . "⛔ ÉCHEC - FAILLES CRITIQUES DÉTECTÉES" . Couleurs::RESET . str_repeat(' ', 32) . "║\n";
        } elseif ($this->testsEchoues === 0) {
            echo "║ " . Couleurs::VERT . Couleurs::GRAS . "✅ SUCCÈS - Tous les tests sont passés!" . Couleurs::RESET . str_repeat(' ', 31) . "║\n";
        } else {
            echo "║ " . Couleurs::JAUNE . "⚠ ATTENTION - $pourcentage% des tests réussis" . Couleurs::RESET . str_repeat(' ', 35) . "║\n";
        }
        
        echo "╚══════════════════════════════════════════════════════════════════════╝\n";
    }
    
    /**
     * Retourne les statistiques du test
     */
    public function getStatistiques(): array {
        return [
            'nom' => $this->nomTest,
            'reussis' => $this->testsReussis,
            'echoues' => $this->testsEchoues,
            'critiques' => $this->testsCritiques,
            'importants' => $this->testsImportants,
            'avertissements' => $this->avertissements,
            'total' => $this->totalTests,
            'duree' => round(microtime(true) - $this->tempsDebut, 2),
            'resultats' => $this->resultats
        ];
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ════════════════════════════════════════════════════════════════════════
    
    private function enregistrerEchec(string $criticite): void {
        if ($criticite === Criticite::CRITIQUE) {
            $this->testsCritiques++;
        } elseif ($criticite === Criticite::IMPORTANT) {
            $this->testsImportants++;
        }
    }
    
    private function formatEchec(string $criticite): string {
        if ($criticite === Criticite::CRITIQUE) {
            return Couleurs::ROUGE . Couleurs::GRAS . "✗ [CRITIQUE] " . Couleurs::RESET;
        } elseif ($criticite === Criticite::IMPORTANT) {
            return Couleurs::ROUGE . "✗ [IMPORTANT] " . Couleurs::RESET;
        }
        return Couleurs::ROUGE . "✗ " . Couleurs::RESET;
    }
}

} // fin if class_exists
