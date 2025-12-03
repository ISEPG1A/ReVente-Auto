<?php

/**
 * Classe gérant la connexion à la base de données via PDO.
 * Utilise le pattern Singleton pour ne créer qu'une seule connexion par requête.
 */
class BaseDeDonnees {
    // Instance unique de la connexion PDO
    private static $instance = null;

    /**
     * Récupère l'instance de connexion à la base de données.
     * Si elle n'existe pas, elle est créée avec les paramètres de configuration.
     * 
     * @return PDO L'objet PDO connecté
     */
    public static function obtenirConnexion() {
        if (self::$instance === null) {
            // Chargement de la configuration
            $configPath = __DIR__ . '/../../config.php';
            if (!file_exists($configPath)) {
                throw new Exception("Fichier de configuration introuvable : $configPath");
            }
            $config = require $configPath;

            $hote = $config['db_host'];
            $port = $config['db_port'];
            $nomBdd = $config['db_name'];
            $utilisateur = $config['db_user'];
            $motDePasse = $config['db_pass'];

            try {
                $dsn = "mysql:host=$hote;port=$port;dbname=$nomBdd;charset=utf8mb4";
                self::$instance = new PDO($dsn, $utilisateur, $motDePasse, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                // En cas d'erreur critique, on arrête tout proprement
                http_response_code(500);
                echo json_encode(['erreur' => 'Erreur de connexion à la base de données : ' . $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }
}
