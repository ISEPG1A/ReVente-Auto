<?php
/**
 * Configuration de la base de données et fonctions utilitaires
 * 
 * Ce fichier contient :
 * - Les constantes de connexion MySQL
 * - La fonction de connexion à la base de données
 * - Les fonctions utilitaires pour la validation et le formatage JSON
 */

// Paramètres de connexion à la base de données MySQL
define('HOTE_BDD', '127.0.0.1');        // Adresse du serveur MySQL
define('PORT_BDD', 3307);                // Port MySQL (3307 pour XAMPP par défaut)
define('NOM_BDD', 'ultra_app');          // Nom de la base de données
define('UTILISATEUR_BDD', 'root');       // Utilisateur MySQL
define('MOT_DE_PASSE_BDD', '');          // Mot de passe MySQL (vide par défaut sur XAMPP)

// Configuration du fuseau horaire
date_default_timezone_set('Europe/Paris');

/**
 * Obtenir une connexion PDO à la base de données
 * Utilise le pattern Singleton pour réutiliser la même connexion
 * 
 * @return PDO Instance de connexion à la base de données
 * @throws RuntimeException Si la connexion échoue
 */
function obtenirConnexionBDD(): PDO {
    static $connexion = null;
    
    // Si la connexion n'existe pas encore, on la crée
    if ($connexion === null) {
        try {
            $dsn = 'mysql:host=' . HOTE_BDD . ';port=' . PORT_BDD . ';dbname=' . NOM_BDD . ';charset=utf8mb4';
            $connexion = new PDO($dsn, UTILISATEUR_BDD, MOT_DE_PASSE_BDD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,              // Lever des exceptions en cas d'erreur
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Récupérer les résultats en tableau associatif
                PDO::ATTR_EMULATE_PREPARES => false,                      // Utiliser les vraies requêtes préparées
            ]);
        } catch (PDOException $erreur) {
            throw new RuntimeException('Erreur connexion base de données: ' . $erreur->getMessage());
        }
    }
    
    return $connexion;
}

/**
 * Envoyer une réponse JSON et terminer le script
 * 
 * @param mixed $donnees Données à encoder en JSON
 * @param int $statut Code de statut HTTP (200 par défaut)
 * @return void
 */
function envoyerJSON($donnees, int $statut = 200): void {
    http_response_code($statut);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Lire et décoder le corps JSON d'une requête
 * 
 * @return array Données décodées ou tableau vide si échec
 */
function lireCorpsJSON(): array {
    $contenuBrut = file_get_contents('php://input');
    if (!$contenuBrut) return [];
    
    $donnees = json_decode($contenuBrut, true);
    return is_array($donnees) ? $donnees : [];
}

/**
 * Valider qu'une chaîne est non vide et respecte une longueur maximale
 * 
 * @param string|null $chaine Chaîne à valider
 * @param int $longueurMax Longueur maximale autorisée (50 par défaut)
 * @return bool True si la chaîne est valide
 */
function chaineValide(?string $chaine, int $longueurMax = 50): bool {
    return is_string($chaine) && $chaine !== '' && mb_strlen($chaine) <= $longueurMax;
}

/**
 * Valider qu'un nombre entier est compris entre deux bornes
 * 
 * @param mixed $nombre Valeur à valider
 * @param int $min Valeur minimale
 * @param int $max Valeur maximale
 * @return bool True si le nombre est valide
 */
function entierEntre($nombre, int $min, int $max): bool {
    return filter_var($nombre, FILTER_VALIDATE_INT) !== false && $nombre >= $min && $nombre <= $max;
}

/**
 * Valider qu'un nombre décimal est supérieur ou égal à une valeur minimale
 * 
 * @param mixed $nombre Valeur à valider
 * @param float $min Valeur minimale
 * @return bool True si le nombre est valide
 */
function nombreMinimum($nombre, float $min): bool {
    return filter_var($nombre, FILTER_VALIDATE_FLOAT) !== false && $nombre >= $min;
}
