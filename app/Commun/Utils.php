<?php

/**
 * Classe utilitaire partagée pour l'application
 * Regroupe les fonctions de réponse HTTP, lecture de requêtes et validation
 */
class Utils {

    /**
     * Envoyer une réponse JSON et terminer le script
     * 
     * @param mixed $donnees Données à encoder en JSON
     * @param int $statut Code de statut HTTP (200 par défaut)
     * @return void
     */
    public static function envoyerJSON($donnees, int $statut = 200): void {
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
    public static function lireCorpsJSON(): array {
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
    public static function chaineValide(?string $chaine, int $longueurMax = 50): bool {
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
    public static function entierEntre($nombre, int $min, int $max): bool {
        return filter_var($nombre, FILTER_VALIDATE_INT) !== false && $nombre >= $min && $nombre <= $max;
    }

    /**
     * Valider qu'un nombre décimal est supérieur ou égal à une valeur minimale
     * 
     * @param mixed $nombre Valeur à valider
     * @param float $min Valeur minimale
     * @return bool True si le nombre est valide
     */
    public static function nombreMinimum($nombre, float $min): bool {
        return filter_var($nombre, FILTER_VALIDATE_FLOAT) !== false && $nombre >= $min;
    }

    /**
     * Valider un format d'email
     */
    public static function emailValide($email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valider un format de téléphone (basique)
     */
    public static function telephoneValide($phone): bool {
        return preg_match('/^[0-9 +().-]{6,}$/', (string)$phone);
    }

    /**
     * Valider la complexité d'un mot de passe
     */
    public static function motDePasseFort($password): bool {
        return strlen($password) >= 8 
            && preg_match('/[a-z]/', $password) 
            && preg_match('/[A-Z]/', $password) 
            && preg_match('/\d/', $password);
    }
}
