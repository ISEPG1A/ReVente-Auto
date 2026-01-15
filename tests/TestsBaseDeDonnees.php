<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TESTS BASE DE DONNÉES - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Tests d'intégrité et de structure de la base de données :
 * - Structure des tables
 * - Contraintes d'intégrité
 * - Index et performances
 * - Relations et clés étrangères
 * - Encodage et collation
 * - Données de référence
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/TestsBase.php';

class TestsBaseDeDonnees extends TestsBase {
    
    protected string $nomTest = 'TESTS BASE DE DONNÉES - ReVente-Auto';
    
    private ?PDO $pdo = null;
    
    public function __construct() {
        parent::__construct();
        $this->connecterBDD();
    }
    
    /**
     * Établit la connexion à la base de données
     */
    private function connecterBDD(): void {
        try {
            // Charger les variables depuis .env (même logique que config.php)
            $envFile = $this->racineProjet . '/.env';
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Ignorer les commentaires
                    if (strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    // Parser la ligne KEY=VALUE
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        if (!getenv($key)) {
                            putenv("$key=$value");
                        }
                    }
                }
            }
            
            // Récupérer les valeurs
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '3306';
            $dbname = getenv('DB_NAME') ?: 'revente_auto';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';
            
            $this->pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 10
                ]
            );
        } catch (PDOException $e) {
            $this->afficherAvertissement("Impossible de se connecter à la BDD: " . $e->getMessage(), Criticite::CRITIQUE);
            $this->pdo = null;
        }
    }
    
    /**
     * Exécute tous les tests de base de données
     */
    public function executer(): array {
        $this->afficherEntete();
        
        if ($this->pdo === null) {
            $this->afficherSection("ERREUR DE CONNEXION");
            $this->assertTrue(false, "Connexion à la base de données", "", Criticite::CRITIQUE);
            $this->afficherResume();
            return $this->getStatistiques();
        }
        
        $this->testerConnexion();
        $this->testerStructureTables();
        $this->testerClesPrimaires();
        $this->testerClesEtrangeres();
        $this->testerIndex();
        $this->testerEncodage();
        $this->testerContraintesIntegrite();
        $this->testerDonneesReference();
        $this->testerPerformanceRequetes();
        $this->testerIntegriteRelations();
        
        $this->afficherResume();
        
        return $this->getStatistiques();
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 1. TEST CONNEXION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerConnexion(): void {
        $this->afficherSection("1. CONNEXION BASE DE DONNÉES");
        
        $this->assertTrue($this->pdo !== null, "Connexion PDO établie", "", Criticite::CRITIQUE);
        
        // Version MySQL/MariaDB
        $stmt = $this->pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        $this->afficherInfo("Version: $version");
        
        // Base de données utilisée
        $stmt = $this->pdo->query("SELECT DATABASE() as db");
        $dbname = $stmt->fetch()['db'];
        $this->assertTrue(!empty($dbname), "Base de données sélectionnée: $dbname");
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 2. STRUCTURE DES TABLES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerStructureTables(): void {
        $this->afficherSection("2. STRUCTURE DES TABLES");
        
        // Tables avec colonnes correspondant à la vraie structure BDD
        $tablesRequises = [
            'users' => ['id', 'email', 'password_hash', 'first_name', 'last_name', 'role', 'created_at'],
            'vehicles' => ['id', 'marque', 'modele', 'prix', 'annee', 'user_id', 'status'],
            'favorites' => ['user_id', 'vehicle_id'],
            'messages' => ['id', 'conversation_id', 'sender_id', 'content'],
            'conversations' => ['id', 'buyer_id', 'seller_id', 'vehicle_id'],
            'contacts' => ['id', 'nom', 'email', 'message'],
            'faq' => ['id', 'question', 'reponse'],
            'cgu_versions' => ['id', 'hash_contenu', 'date_creation']
        ];
        
        // Récupérer la liste des tables
        $stmt = $this->pdo->query("SHOW TABLES");
        $tablesExistantes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tablesRequises as $table => $colonnesRequises) {
            $existe = in_array($table, $tablesExistantes);
            $this->assertTrue($existe, "Table '$table' existe", "", Criticite::CRITIQUE);
            
            if ($existe) {
                // Vérifier les colonnes
                $stmt = $this->pdo->query("DESCRIBE `$table`");
                $colonnes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($colonnesRequises as $colonne) {
                    $colonneExiste = in_array($colonne, $colonnes);
                    $this->assertTrue($colonneExiste, "Table '$table': colonne '$colonne' existe", "", Criticite::IMPORTANT);
                }
            }
        }
        
        // Afficher toutes les tables
        $this->afficherInfo("Tables trouvées: " . implode(', ', $tablesExistantes));
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 3. CLÉS PRIMAIRES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerClesPrimaires(): void {
        $this->afficherSection("3. CLÉS PRIMAIRES");
        
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'");
            $pk = $stmt->fetch();
            
            $this->assertTrue($pk !== false, "Table '$table' a une clé primaire", "", Criticite::IMPORTANT);
            
            if ($pk) {
                // Vérifier auto_increment si c'est une colonne 'id'
                $stmt = $this->pdo->query("DESCRIBE `$table` `{$pk['Column_name']}`");
                $info = $stmt->fetch();
                
                if ($pk['Column_name'] === 'id') {
                    $autoInc = strpos($info['Extra'] ?? '', 'auto_increment') !== false;
                    $this->assertTrue($autoInc, "Table '$table'.id est auto_increment");
                }
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 4. CLÉS ÉTRANGÈRES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerClesEtrangeres(): void {
        $this->afficherSection("4. CLÉS ÉTRANGÈRES");
        
        // Relations attendues avec noms anglais (compatibles avec la BDD)
        $relationsAttendues = [
            'vehicles' => ['user_id' => 'users.id'],
            'favorites' => ['user_id' => 'users.id', 'vehicle_id' => 'vehicles.id'],
            'messages' => ['conversation_id' => 'conversations.id', 'sender_id' => 'users.id'],
            'conversations' => ['buyer_id' => 'users.id', 'seller_id' => 'users.id', 'vehicle_id' => 'vehicles.id']
        ];
        
        // Récupérer les clés étrangères avec alias explicites
        $dbname = $this->pdo->query("SELECT DATABASE()")->fetchColumn();
        $stmt = $this->pdo->query("
            SELECT kcu.TABLE_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE kcu
            WHERE kcu.TABLE_SCHEMA = '$dbname'
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fks = $stmt->fetchAll();
        
        $fkMap = [];
        foreach ($fks as $fk) {
            $fkMap[$fk['TABLE_NAME']][$fk['COLUMN_NAME']] = $fk['REFERENCED_TABLE_NAME'] . '.' . $fk['REFERENCED_COLUMN_NAME'];
        }
        
        foreach ($relationsAttendues as $table => $relations) {
            // Vérifier que la table existe d'abord
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if (!$stmt->fetch()) continue;
            
            foreach ($relations as $colonne => $reference) {
                $existe = isset($fkMap[$table][$colonne]) && $fkMap[$table][$colonne] === $reference;
                $this->assertTrue($existe, "FK '$table.$colonne' → '$reference'", "", Criticite::IMPORTANT);
            }
        }
        
        // Vérifier ON DELETE CASCADE ou SET NULL
        $this->afficherSousSection("Règles de suppression");
        $stmt = $this->pdo->query("
            SELECT rc.TABLE_NAME, rc.DELETE_RULE, rc.CONSTRAINT_NAME
            FROM information_schema.REFERENTIAL_CONSTRAINTS rc
            WHERE rc.CONSTRAINT_SCHEMA = '$dbname'
        ");
        $deleteRules = $stmt->fetchAll();
        
        foreach ($deleteRules as $rule) {
            $this->afficherInfo("{$rule['TABLE_NAME']} ({$rule['CONSTRAINT_NAME']}): ON DELETE {$rule['DELETE_RULE']}");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 5. INDEX
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerIndex(): void {
        $this->afficherSection("5. INDEX DE PERFORMANCE");
        
        $indexRecommandes = [
            'users' => ['email'],
            'vehicles' => ['marque', 'prix', 'annee', 'user_id', 'status'],
            'favorites' => ['user_id', 'vehicle_id'],
            'messages' => ['conversation_id', 'sender_id', 'created_at'],
            'conversations' => ['buyer_id', 'seller_id', 'vehicle_id']
        ];
        
        foreach ($indexRecommandes as $table => $colonnes) {
            // Vérifier si la table existe
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if (!$stmt->fetch()) continue;
            
            // Récupérer les index
            $stmt = $this->pdo->query("SHOW INDEX FROM `$table`");
            $indexes = $stmt->fetchAll();
            
            $colonnesIndexees = array_column($indexes, 'Column_name');
            
            foreach ($colonnes as $colonne) {
                $indexe = in_array($colonne, $colonnesIndexees);
                $this->assertTrue($indexe, "Index sur '$table.$colonne'", 
                    $indexe ? "" : "Recommandé pour les performances");
            }
        }
        
        // Index uniques
        $this->afficherSousSection("Index uniques");
        
        $uniqueAttendus = [
            'users' => ['email']
        ];
        
        foreach ($uniqueAttendus as $table => $colonnes) {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if (!$stmt->fetch()) continue;
            
            $stmt = $this->pdo->query("SHOW INDEX FROM `$table` WHERE Non_unique = 0");
            $uniqueIndexes = $stmt->fetchAll();
            $colonnesUniques = array_column($uniqueIndexes, 'Column_name');
            
            foreach ($colonnes as $colonne) {
                $unique = in_array($colonne, $colonnesUniques);
                $this->assertTrue($unique, "Index unique sur '$table.$colonne'", "", Criticite::IMPORTANT);
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 6. ENCODAGE ET COLLATION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerEncodage(): void {
        $this->afficherSection("6. ENCODAGE ET COLLATION");
        
        // Encodage de la base
        $stmt = $this->pdo->query("SELECT @@character_set_database as charset, @@collation_database as collation");
        $dbInfo = $stmt->fetch();
        
        $this->assertContient($dbInfo['charset'], 'utf8', "Base en UTF-8", "", Criticite::IMPORTANT);
        $this->afficherInfo("Charset BDD: {$dbInfo['charset']}, Collation: {$dbInfo['collation']}");
        
        // Vérifier les tables
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SHOW TABLE STATUS LIKE '$table'");
            $info = $stmt->fetch();
            
            if ($info) {
                $utf8 = strpos($info['Collation'] ?? '', 'utf8') !== false;
                $this->assertTrue($utf8, "Table '$table' en UTF-8", $info['Collation'] ?? 'inconnu');
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 7. CONTRAINTES D'INTÉGRITÉ
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerContraintesIntegrite(): void {
        $this->afficherSection("7. CONTRAINTES D'INTÉGRITÉ");
        
        // Contraintes NOT NULL
        $this->afficherSousSection("Champs NOT NULL obligatoires");
        
        $notNullAttendus = [
            'users' => ['email', 'password'],
            'vehicles' => ['prix'],  // user_id peut être NULL si utilisateur supprimé
            'contacts' => ['email', 'message']
        ];
        
        foreach ($notNullAttendus as $table => $colonnes) {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if (!$stmt->fetch()) continue;
            
            foreach ($colonnes as $colonne) {
                try {
                    $stmt = $this->pdo->query("DESCRIBE `$table` `$colonne`");
                    $info = $stmt->fetch();
                    
                    if ($info) {
                        $notNull = $info['Null'] === 'NO';
                        $this->assertTrue($notNull, "'$table.$colonne' est NOT NULL", "", Criticite::IMPORTANT);
                    }
                } catch (PDOException $e) {
                    $this->afficherInfo("Colonne '$table.$colonne' non trouvée");
                }
            }
        }
        
        // Valeurs par défaut
        $this->afficherSousSection("Valeurs par défaut");
        
        $defaultsAttendus = [
            'users' => ['role' => 'utilisateur', 'is_verified' => '0'],
            'vehicles' => ['statut' => 'en_attente']
        ];
        
        foreach ($defaultsAttendus as $table => $defaults) {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if (!$stmt->fetch()) continue;
            
            foreach ($defaults as $colonne => $valeur) {
                try {
                    $stmt = $this->pdo->query("DESCRIBE `$table` `$colonne`");
                    $info = $stmt->fetch();
                    
                    if ($info) {
                        $this->afficherInfo("'$table.$colonne' default: " . ($info['Default'] ?? 'null'));
                    }
                } catch (PDOException $e) {
                    // Colonne non trouvée
                }
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 8. DONNÉES DE RÉFÉRENCE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerDonneesReference(): void {
        $this->afficherSection("8. DONNÉES DE RÉFÉRENCE");
        
        // Vérifier qu'il y a au moins un admin
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->fetch()) {
            $stmt = $this->pdo->query("SELECT COUNT(*) as nb FROM users WHERE role = 'admin'");
            $result = $stmt->fetch();
            $this->assertTrue($result['nb'] > 0, "Au moins un administrateur existe", "", Criticite::IMPORTANT);
        }
        
        // Vérifier les FAQ
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'faq'");
        if ($stmt->fetch()) {
            $stmt = $this->pdo->query("SELECT COUNT(*) as nb FROM faq");
            $result = $stmt->fetch();
            $this->assertTrue($result['nb'] > 0, "FAQ contient des entrées");
        }
        
        // Vérifier les CGU
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'cgu_versions'");
        if ($stmt->fetch()) {
            $stmt = $this->pdo->query("SELECT COUNT(*) as nb FROM cgu_versions");
            $result = $stmt->fetch();
            $this->assertTrue($result['nb'] > 0, "CGU contient au moins une version", "", Criticite::IMPORTANT);
        }
        
        // Statistiques générales
        $this->afficherSousSection("Statistiques");
        
        $tables = ['users', 'vehicles', 'favorites', 'messages', 'contacts', 'conversations'];
        foreach ($tables as $table) {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->fetch()) {
                $stmt = $this->pdo->query("SELECT COUNT(*) as nb FROM `$table`");
                $result = $stmt->fetch();
                $this->afficherInfo("$table: {$result['nb']} enregistrements");
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 9. PERFORMANCE DES REQUÊTES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerPerformanceRequetes(): void {
        $this->afficherSection("9. PERFORMANCE DES REQUÊTES");
        
        $requetes = [
            "SELECT * FROM vehicles WHERE statut = 'publie' LIMIT 20" => "Liste véhicules publiés",
            "SELECT * FROM users WHERE email = 'test@test.com'" => "Recherche par email",
            "SELECT COUNT(*) FROM vehicles" => "Comptage véhicules"
        ];
        
        foreach ($requetes as $sql => $description) {
            // Vérifier que les tables existent
            preg_match('/FROM\s+(\w+)/i', $sql, $matches);
            $table = $matches[1] ?? '';
            
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if (!$stmt->fetch()) continue;
            
            // EXPLAIN
            try {
                $stmt = $this->pdo->query("EXPLAIN $sql");
                $explain = $stmt->fetch();
                
                if ($explain) {
                    $type = $explain['type'] ?? 'unknown';
                    $rows = $explain['rows'] ?? 0;
                    
                    // Vérifier qu'on n'a pas de full table scan sur grande table
                    $optimal = !in_array($type, ['ALL']) || $rows < 1000;
                    $this->assertTrue($optimal, "$description - Type: $type", "Rows estimées: $rows");
                }
            } catch (PDOException $e) {
                $this->afficherInfo("$description: ne peut pas être testé");
            }
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 10. INTÉGRITÉ DES RELATIONS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerIntegriteRelations(): void {
        $this->afficherSection("10. INTÉGRITÉ DES RELATIONS");
        
        // Vérifier qu'il n'y a pas d'orphelins (avec noms anglais)
        $verifications = [
            ['vehicles', 'user_id', 'users', 'id', 'Véhicules sans propriétaire'],
            ['favorites', 'vehicle_id', 'vehicles', 'id', 'Favoris vers véhicule inexistant'],
            ['favorites', 'user_id', 'users', 'id', 'Favoris vers utilisateur inexistant'],
            ['messages', 'conversation_id', 'conversations', 'id', 'Messages sans conversation'],
            ['conversations', 'buyer_id', 'users', 'id', 'Conversation vers acheteur inexistant'],
            ['conversations', 'seller_id', 'users', 'id', 'Conversation vers vendeur inexistant'],
            ['conversations', 'vehicle_id', 'vehicles', 'id', 'Conversation vers véhicule inexistant']
        ];
        
        foreach ($verifications as [$table, $colonne, $refTable, $refColonne, $description]) {
            // Vérifier que les tables existent
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if (!$stmt->fetch()) continue;
            
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$refTable'");
            if (!$stmt->fetch()) continue;
            
            // Vérifier que la colonne existe
            try {
                $stmt = $this->pdo->query("DESCRIBE `$table` `$colonne`");
                if (!$stmt->fetch()) continue;
            } catch (PDOException $e) {
                continue;
            }
            
            $sql = "SELECT COUNT(*) as orphelins FROM `$table` t 
                    LEFT JOIN `$refTable` r ON t.`$colonne` = r.`$refColonne` 
                    WHERE r.`$refColonne` IS NULL AND t.`$colonne` IS NOT NULL";
            
            try {
                $stmt = $this->pdo->query($sql);
                $result = $stmt->fetch();
                
                $pasOrphelins = $result['orphelins'] == 0;
                $this->assertTrue($pasOrphelins, $description, 
                    $pasOrphelins ? "" : "{$result['orphelins']} orphelins trouvés", 
                    Criticite::IMPORTANT);
            } catch (PDOException $e) {
                $this->afficherInfo("$description: ne peut pas être vérifié");
            }
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXÉCUTION DIRECTE
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['argv'][0] ?? '')) {
    $tests = new TestsBaseDeDonnees();
    $resultats = $tests->executer();
    exit($resultats['echoues'] > 0 ? 1 : 0);
}
