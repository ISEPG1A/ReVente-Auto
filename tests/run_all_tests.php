<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * LANCEUR DE TESTS CENTRALISÉ - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce script lance tous les tests disponibles et génère un rapport consolidé.
 * 
 * Usage:
 *   php run_all_tests.php              # Lance tous les tests
 *   php run_all_tests.php securite     # Lance uniquement les tests de sécurité
 *   php run_all_tests.php --help       # Affiche l'aide
 *   php run_all_tests.php --list       # Liste les tests disponibles
 *   php run_all_tests.php --json       # Sortie en JSON
 *   php run_all_tests.php --html       # Génère un rapport HTML
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Paris');

// Couleurs terminal
class CouleursTerminal {
    const VERT = "\033[32m";
    const ROUGE = "\033[31m";
    const JAUNE = "\033[33m";
    const BLEU = "\033[34m";
    const CYAN = "\033[36m";
    const MAGENTA = "\033[35m";
    const RESET = "\033[0m";
    const GRAS = "\033[1m";
}

/**
 * Classe principale du lanceur de tests
 */
class LanceurTests {
    
    private array $testsDisponibles = [];
    private array $resultatsGlobaux = [];
    private float $tempsDebut;
    private string $dossierTests;
    private bool $sortieJson = false;
    private bool $sortieHtml = false;
    private bool $sortieFichier = false;
    private string $fichierSortie = '';
    private $handleFichier = null;
    
    public function __construct() {
        $this->tempsDebut = microtime(true);
        $this->dossierTests = __DIR__;
        $this->chargerTests();
    }
    
    /**
     * Démarre la capture de sortie vers un fichier
     */
    private function demarrerCaptureFichier(): void {
        if ($this->sortieFichier && $this->fichierSortie) {
            $this->handleFichier = fopen($this->fichierSortie, 'w');
            if ($this->handleFichier) {
                // Fonction pour écrire à la fois dans le terminal et le fichier
                ob_start(function($buffer) {
                    if ($this->handleFichier) {
                        // Supprimer les codes couleur ANSI pour le fichier
                        $bufferClean = preg_replace('/\033\[[0-9;]*m/', '', $buffer);
                        fwrite($this->handleFichier, $bufferClean);
                    }
                    return $buffer; // Retourner le buffer original pour le terminal
                }, 1);
            }
        }
    }
    
    /**
     * Arrête la capture de sortie vers un fichier
     */
    private function arreterCaptureFichier(): void {
        if ($this->handleFichier) {
            ob_end_flush();
            fclose($this->handleFichier);
            $this->handleFichier = null;
            echo "\n📄 Rapport enregistré dans: " . $this->fichierSortie . "\n";
        }
    }
    
    /**
     * Charge la liste des tests disponibles
     */
    private function chargerTests(): void {
        $this->testsDisponibles = [
            'securite' => [
                'fichier' => 'TestsSecurite.php',
                'classe' => 'TestsSecurite',
                'description' => 'Tests de sécurité (CSRF, XSS, SQL injection, etc.)',
                'priorite' => 1
            ],
            'api' => [
                'fichier' => 'TestsAPI.php',
                'classe' => 'TestsAPI',
                'description' => 'Tests exhaustifs des API',
                'priorite' => 2
            ],
            'validation' => [
                'fichier' => 'TestsValidation.php',
                'classe' => 'TestsValidation',
                'description' => 'Tests de validation des données',
                'priorite' => 3
            ],
            'performance' => [
                'fichier' => 'TestsPerformance.php',
                'classe' => 'TestsPerformance',
                'description' => 'Tests de performance',
                'priorite' => 4
            ],
            'bdd' => [
                'fichier' => 'TestsBaseDeDonnees.php',
                'classe' => 'TestsBaseDeDonnees',
                'description' => 'Tests d\'intégrité de la base de données',
                'priorite' => 5
            ],
            'accessibilite' => [
                'fichier' => 'TestsAccessibilite.php',
                'classe' => 'TestsAccessibilite',
                'description' => 'Tests d\'accessibilité web (WCAG)',
                'priorite' => 6
            ],
            'emails' => [
                'fichier' => 'TestsEmails.php',
                'classe' => 'TestsEmails',
                'description' => 'Tests d\'envoi d\'emails (SMTP)',
                'priorite' => 7
            ]
        ];
    }
    
    /**
     * Point d'entrée principal
     */
    public function executer(array $args): int {
        // Parser les arguments
        $testsALancer = $this->parserArguments($args);
        
        if ($testsALancer === null) {
            return 0; // Aide affichée
        }
        
        // Démarrer la capture vers fichier si demandé
        $this->demarrerCaptureFichier();
        
        if (!$this->sortieJson) {
            $this->afficherEntete();
        }
        
        // Charger la classe de base
        require_once $this->dossierTests . '/TestsBase.php';
        
        // En mode JSON, capturer la sortie pour éviter la pollution
        if ($this->sortieJson) {
            ob_start();
        }
        
        // Lancer les tests
        foreach ($testsALancer as $nomTest) {
            if (isset($this->testsDisponibles[$nomTest])) {
                $this->lancerTest($nomTest);
            }
        }
        
        // Fin de capture en mode JSON
        if ($this->sortieJson) {
            ob_end_clean();
        }
        
        // Générer le rapport
        if ($this->sortieJson) {
            echo json_encode($this->genererRapportJSON(), JSON_PRETTY_PRINT);
        } elseif ($this->sortieHtml) {
            $this->genererRapportHTML();
        } else {
            $this->afficherRapportConsolide();
        }
        
        // Arrêter la capture fichier
        $this->arreterCaptureFichier();
        
        // Code de retour
        $echecTotal = array_sum(array_column($this->resultatsGlobaux, 'echoues'));
        $critiquesTotal = array_sum(array_column($this->resultatsGlobaux, 'critiques'));
        
        return $critiquesTotal > 0 ? 2 : ($echecTotal > 0 ? 1 : 0);
    }
    
    /**
     * Parse les arguments de la ligne de commande
     */
    private function parserArguments(array $args): ?array {
        // Retirer le nom du script
        array_shift($args);
        
        // Options
        $testsSpecifiques = [];
        
        for ($i = 0; $i < count($args); $i++) {
            $arg = $args[$i];
            switch ($arg) {
                case '--help':
                case '-h':
                    $this->afficherAide();
                    return null;
                    
                case '--list':
                case '-l':
                    $this->afficherListeTests();
                    return null;
                    
                case '--json':
                    $this->sortieJson = true;
                    break;
                    
                case '--html':
                    $this->sortieHtml = true;
                    break;
                
                case '--output':
                case '-o':
                    // Récupérer le fichier de sortie
                    if (isset($args[$i + 1]) && !str_starts_with($args[$i + 1], '-')) {
                        $this->sortieFichier = true;
                        $this->fichierSortie = $args[$i + 1];
                        $i++; // Sauter le prochain argument
                    }
                    break;
                    
                default:
                    // Vérifier si c'est --output=fichier
                    if (str_starts_with($arg, '--output=')) {
                        $this->sortieFichier = true;
                        $this->fichierSortie = substr($arg, 9);
                    } elseif (!str_starts_with($arg, '-')) {
                        $testsSpecifiques[] = strtolower($arg);
                    }
            }
        }
        
        // Si aucun test spécifié, tous les tests
        if (empty($testsSpecifiques)) {
            $testsSpecifiques = array_keys($this->testsDisponibles);
        }
        
        // Trier par priorité
        usort($testsSpecifiques, function($a, $b) {
            $prioriteA = $this->testsDisponibles[$a]['priorite'] ?? 99;
            $prioriteB = $this->testsDisponibles[$b]['priorite'] ?? 99;
            return $prioriteA - $prioriteB;
        });
        
        return $testsSpecifiques;
    }
    
    /**
     * Lance un test spécifique
     */
    private function lancerTest(string $nomTest): void {
        $config = $this->testsDisponibles[$nomTest];
        $fichier = $this->dossierTests . '/' . $config['fichier'];
        
        if (!file_exists($fichier)) {
            if (!$this->sortieJson) {
                echo CouleursTerminal::JAUNE . "⚠ Fichier non trouvé: {$config['fichier']}" . CouleursTerminal::RESET . "\n";
            }
            return;
        }
        
        if (!$this->sortieJson) {
            echo "\n";
            echo CouleursTerminal::MAGENTA . "╔════════════════════════════════════════════════════════════════════╗" . CouleursTerminal::RESET . "\n";
            echo CouleursTerminal::MAGENTA . "║ " . str_pad(strtoupper($nomTest), 66) . " ║" . CouleursTerminal::RESET . "\n";
            echo CouleursTerminal::MAGENTA . "╚════════════════════════════════════════════════════════════════════╝" . CouleursTerminal::RESET . "\n";
        }
        
        require_once $fichier;
        
        if ($config['classe']) {
            // Test avec classe
            $classe = $config['classe'];
            if (class_exists($classe)) {
                $instance = new $classe();
                $resultats = $instance->executer();
                $this->resultatsGlobaux[$nomTest] = $resultats;
            }
        } else {
            // Script simple (comme test_emails.php)
            $this->resultatsGlobaux[$nomTest] = [
                'nom' => $config['description'],
                'reussis' => 0,
                'echoues' => 0,
                'critiques' => 0,
                'note' => 'Script exécuté'
            ];
        }
    }
    
    /**
     * Affiche l'en-tête
     */
    private function afficherEntete(): void {
        echo CouleursTerminal::GRAS . "\n";
        echo "╔══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                                                                          ║\n";
        echo "║     ██████╗ ███████╗██╗   ██╗███████╗███╗   ██╗████████╗███████╗        ║\n";
        echo "║     ██╔══██╗██╔════╝██║   ██║██╔════╝████╗  ██║╚══██╔══╝██╔════╝        ║\n";
        echo "║     ██████╔╝█████╗  ██║   ██║█████╗  ██╔██╗ ██║   ██║   █████╗          ║\n";
        echo "║     ██╔══██╗██╔══╝  ╚██╗ ██╔╝██╔══╝  ██║╚██╗██║   ██║   ██╔══╝          ║\n";
        echo "║     ██║  ██║███████╗ ╚████╔╝ ███████╗██║ ╚████║   ██║   ███████╗        ║\n";
        echo "║     ╚═╝  ╚═╝╚══════╝  ╚═══╝  ╚══════╝╚═╝  ╚═══╝   ╚═╝   ╚══════╝        ║\n";
        echo "║                                                                          ║\n";
        echo "║              SUITE DE TESTS AUTOMATISÉS - ReVente-Auto                  ║\n";
        echo "║                                                                          ║\n";
        echo "╠══════════════════════════════════════════════════════════════════════════╣\n";
        echo "║  Date: " . str_pad(date('Y-m-d H:i:s'), 66) . "║\n";
        echo "║  Version: " . str_pad('3.0.0', 63) . "║\n";
        echo "╚══════════════════════════════════════════════════════════════════════════╝\n";
        echo CouleursTerminal::RESET;
    }
    
    /**
     * Affiche l'aide
     */
    private function afficherAide(): void {
        echo CouleursTerminal::GRAS . "\nLANCEUR DE TESTS - ReVente-Auto\n" . CouleursTerminal::RESET;
        echo str_repeat("═", 50) . "\n\n";
        
        echo CouleursTerminal::CYAN . "Usage:\n" . CouleursTerminal::RESET;
        echo "  php run_all_tests.php [options] [tests...]\n\n";
        
        echo CouleursTerminal::CYAN . "Options:\n" . CouleursTerminal::RESET;
        echo "  --help, -h          Affiche cette aide\n";
        echo "  --list, -l          Liste les tests disponibles\n";
        echo "  --json              Sortie au format JSON\n";
        echo "  --html              Génère un rapport HTML\n";
        echo "  --output, -o FILE   Enregistre la sortie dans un fichier\n\n";
        
        echo CouleursTerminal::CYAN . "Exemples:\n" . CouleursTerminal::RESET;
        echo "  php run_all_tests.php                              # Lance tous les tests\n";
        echo "  php run_all_tests.php securite                     # Lance les tests de sécurité\n";
        echo "  php run_all_tests.php securite api                 # Lance sécurité + API\n";
        echo "  php run_all_tests.php --output=rapport.txt         # Enregistre dans fichier\n";
        echo "  php run_all_tests.php -o rapport.txt               # Idem (forme courte)\n";
        echo "  php run_all_tests.php --json > rapport.json\n";
        echo "  php run_all_tests.php --html\n\n";
        
        echo CouleursTerminal::CYAN . "Tests disponibles:\n" . CouleursTerminal::RESET;
        $this->afficherListeTests();
    }
    
    /**
     * Affiche la liste des tests
     */
    private function afficherListeTests(): void {
        echo "\n";
        foreach ($this->testsDisponibles as $nom => $config) {
            $fichierExiste = file_exists($this->dossierTests . '/' . $config['fichier']);
            $status = $fichierExiste 
                ? CouleursTerminal::VERT . "✓" . CouleursTerminal::RESET 
                : CouleursTerminal::ROUGE . "✗" . CouleursTerminal::RESET;
            
            echo "  $status " . CouleursTerminal::GRAS . str_pad($nom, 15) . CouleursTerminal::RESET;
            echo " - " . $config['description'] . "\n";
        }
        echo "\n";
    }
    
    /**
     * Affiche le rapport consolidé
     */
    private function afficherRapportConsolide(): void {
        $duree = round(microtime(true) - $this->tempsDebut, 2);
        
        echo "\n\n";
        echo CouleursTerminal::GRAS;
        echo "╔══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                        RAPPORT CONSOLIDÉ                                 ║\n";
        echo "╠══════════════════════════════════════════════════════════════════════════╣\n";
        echo CouleursTerminal::RESET;
        
        $totalReussis = 0;
        $totalEchoues = 0;
        $totalCritiques = 0;
        $totalImportants = 0;
        
        foreach ($this->resultatsGlobaux as $nomTest => $resultats) {
            $reussis = $resultats['reussis'] ?? 0;
            $echoues = $resultats['echoues'] ?? 0;
            $critiques = $resultats['critiques'] ?? 0;
            $importants = $resultats['importants'] ?? 0;
            
            $totalReussis += $reussis;
            $totalEchoues += $echoues;
            $totalCritiques += $critiques;
            $totalImportants += $importants;
            
            $status = $echoues === 0 
                ? CouleursTerminal::VERT . "✓ PASS" . CouleursTerminal::RESET
                : ($critiques > 0 
                    ? CouleursTerminal::ROUGE . "✗ FAIL" . CouleursTerminal::RESET 
                    : CouleursTerminal::JAUNE . "⚠ WARN" . CouleursTerminal::RESET);
            
            $total = $reussis + $echoues;
            $pourcentage = $total > 0 ? round(($reussis / $total) * 100) : 0;
            
            echo "║ $status " . str_pad(ucfirst($nomTest), 12) . " │ ";
            echo str_pad("$reussis/$total ($pourcentage%)", 18);
            echo " │ Critiques: " . str_pad($critiques, 3);
            echo str_repeat(' ', 7) . "║\n";
        }
        
        echo "╠══════════════════════════════════════════════════════════════════════════╣\n";
        
        // Totaux
        $total = $totalReussis + $totalEchoues;
        $pourcentageGlobal = $total > 0 ? round(($totalReussis / $total) * 100, 1) : 0;
        
        echo "║ " . CouleursTerminal::GRAS . "TOTAL" . CouleursTerminal::RESET . "        │ ";
        echo CouleursTerminal::VERT . str_pad("Réussis: $totalReussis", 18) . CouleursTerminal::RESET;
        echo " │ ";
        echo CouleursTerminal::ROUGE . "Échoués: " . str_pad($totalEchoues, 5) . CouleursTerminal::RESET;
        echo str_repeat(' ', 11) . "║\n";
        
        if ($totalCritiques > 0) {
            echo "║ " . CouleursTerminal::ROUGE . CouleursTerminal::GRAS;
            echo str_pad("⚠ FAILLES CRITIQUES: $totalCritiques", 72);
            echo CouleursTerminal::RESET . "║\n";
        }
        
        if ($totalImportants > 0) {
            echo "║ " . CouleursTerminal::JAUNE;
            echo str_pad("⚠ Failles importantes: $totalImportants", 72);
            echo CouleursTerminal::RESET . "║\n";
        }
        
        echo "╠══════════════════════════════════════════════════════════════════════════╣\n";
        echo "║ Durée totale: " . str_pad($duree . 's', 60) . "║\n";
        echo "║ Score global: " . str_pad($pourcentageGlobal . '%', 60) . "║\n";
        echo "╠══════════════════════════════════════════════════════════════════════════╣\n";
        
        // Verdict final
        if ($totalCritiques > 0) {
            echo "║ " . CouleursTerminal::ROUGE . CouleursTerminal::GRAS;
            echo "⛔ ÉCHEC - Des failles critiques ont été détectées!              ";
            echo CouleursTerminal::RESET . "    ║\n";
        } elseif ($totalEchoues === 0) {
            echo "║ " . CouleursTerminal::VERT . CouleursTerminal::GRAS;
            echo "✅ SUCCÈS - Tous les tests sont passés!                          ";
            echo CouleursTerminal::RESET . "    ║\n";
        } else {
            echo "║ " . CouleursTerminal::JAUNE . CouleursTerminal::GRAS;
            echo "⚠ ATTENTION - Certains tests ont échoué ($pourcentageGlobal% de réussite)      ";
            echo CouleursTerminal::RESET . "    ║\n";
        }
        
        echo "╚══════════════════════════════════════════════════════════════════════════╝\n";
    }
    
    /**
     * Génère le rapport JSON
     */
    private function genererRapportJSON(): array {
        $duree = round(microtime(true) - $this->tempsDebut, 2);
        
        $totalReussis = array_sum(array_column($this->resultatsGlobaux, 'reussis'));
        $totalEchoues = array_sum(array_column($this->resultatsGlobaux, 'echoues'));
        $totalCritiques = array_sum(array_column($this->resultatsGlobaux, 'critiques'));
        
        return [
            'date' => date('Y-m-d H:i:s'),
            'duree_secondes' => $duree,
            'resume' => [
                'total_tests' => $totalReussis + $totalEchoues,
                'reussis' => $totalReussis,
                'echoues' => $totalEchoues,
                'critiques' => $totalCritiques,
                'pourcentage_reussite' => $totalReussis + $totalEchoues > 0 
                    ? round(($totalReussis / ($totalReussis + $totalEchoues)) * 100, 1) 
                    : 0
            ],
            'tests' => $this->resultatsGlobaux,
            'verdict' => $totalCritiques > 0 ? 'ECHEC_CRITIQUE' : ($totalEchoues > 0 ? 'ECHEC' : 'SUCCES')
        ];
    }
    
    /**
     * Génère le rapport HTML
     */
    private function genererRapportHTML(): void {
        $rapport = $this->genererRapportJSON();
        $fichierRapport = $this->dossierTests . '/rapport_tests_' . date('Y-m-d_H-i-s') . '.html';
        
        $html = $this->genererHTMLRapport($rapport);
        file_put_contents($fichierRapport, $html);
        
        echo CouleursTerminal::VERT . "✓ Rapport HTML généré: $fichierRapport" . CouleursTerminal::RESET . "\n";
    }
    
    /**
     * Génère le contenu HTML du rapport
     */
    private function genererHTMLRapport(array $rapport): string {
        $verdictClass = match($rapport['verdict']) {
            'SUCCES' => 'success',
            'ECHEC_CRITIQUE' => 'danger',
            default => 'warning'
        };
        
        $testsHTML = '';
        foreach ($rapport['tests'] as $nom => $resultats) {
            $reussis = $resultats['reussis'] ?? 0;
            $echoues = $resultats['echoues'] ?? 0;
            $total = $reussis + $echoues;
            $pourcentage = $total > 0 ? round(($reussis / $total) * 100) : 0;
            
            $statusClass = $echoues === 0 ? 'success' : (($resultats['critiques'] ?? 0) > 0 ? 'danger' : 'warning');
            
            $testsHTML .= "
                <div class='test-item'>
                    <div class='test-name'>
                        <span class='badge badge-{$statusClass}'>" . ($echoues === 0 ? 'PASS' : 'FAIL') . "</span>
                        " . ucfirst($nom) . "
                    </div>
                    <div class='test-stats'>
                        <div class='progress' style='width: 200px'>
                            <div class='progress-bar bg-{$statusClass}' style='width: {$pourcentage}%'></div>
                        </div>
                        <span>{$reussis}/{$total} ({$pourcentage}%)</span>
                    </div>
                </div>";
        }
        
        return "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Rapport de Tests - ReVente-Auto</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #f59e0b, #8b5cf6); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; margin-bottom: 10px; }
        .header .date { opacity: 0.8; }
        .card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h2 { font-size: 18px; margin-bottom: 15px; color: #333; }
        .summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .summary-item { text-align: center; padding: 15px; border-radius: 8px; }
        .summary-item.success { background: #d4edda; color: #155724; }
        .summary-item.danger { background: #f8d7da; color: #721c24; }
        .summary-item.warning { background: #fff3cd; color: #856404; }
        .summary-item.info { background: #cce5ff; color: #004085; }
        .summary-item .value { font-size: 32px; font-weight: bold; }
        .summary-item .label { font-size: 12px; text-transform: uppercase; }
        .test-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .test-item:last-child { border-bottom: none; }
        .test-name { font-weight: 500; }
        .test-stats { display: flex; align-items: center; gap: 15px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .progress { height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden; }
        .progress-bar { height: 100%; }
        .bg-success { background: #28a745; }
        .bg-danger { background: #dc3545; }
        .bg-warning { background: #ffc107; }
        .verdict { padding: 20px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 18px; }
        .verdict.success { background: #d4edda; color: #155724; }
        .verdict.danger { background: #f8d7da; color: #721c24; }
        .verdict.warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🚗 Rapport de Tests - ReVente-Auto</h1>
            <div class='date'>Généré le {$rapport['date']} - Durée: {$rapport['duree_secondes']}s</div>
        </div>
        
        <div class='card'>
            <h2>📊 Résumé</h2>
            <div class='summary'>
                <div class='summary-item info'>
                    <div class='value'>{$rapport['resume']['total_tests']}</div>
                    <div class='label'>Total tests</div>
                </div>
                <div class='summary-item success'>
                    <div class='value'>{$rapport['resume']['reussis']}</div>
                    <div class='label'>Réussis</div>
                </div>
                <div class='summary-item danger'>
                    <div class='value'>{$rapport['resume']['echoues']}</div>
                    <div class='label'>Échoués</div>
                </div>
                <div class='summary-item " . ($rapport['resume']['critiques'] > 0 ? 'danger' : 'success') . "'>
                    <div class='value'>{$rapport['resume']['critiques']}</div>
                    <div class='label'>Critiques</div>
                </div>
            </div>
        </div>
        
        <div class='card'>
            <h2>🧪 Détail des Tests</h2>
            {$testsHTML}
        </div>
        
        <div class='verdict {$verdictClass}'>
            " . ($rapport['verdict'] === 'SUCCES' 
                ? '✅ Tous les tests sont passés!' 
                : ($rapport['verdict'] === 'ECHEC_CRITIQUE' 
                    ? '⛔ Des failles critiques ont été détectées!' 
                    : '⚠️ Certains tests ont échoué')) . "
        </div>
    </div>
</body>
</html>";
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXÉCUTION
// ═══════════════════════════════════════════════════════════════════════════

// Vérifier cURL
if (!function_exists('curl_init')) {
    echo "Erreur: L'extension cURL PHP est requise.\n";
    exit(1);
}

$lanceur = new LanceurTests();
exit($lanceur->executer($argv));
