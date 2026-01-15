<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CLASSE UTILITAIRES - FONCTIONS COMMUNES DE L'APPLICATION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Regroupe les fonctions utilitaires partagées dans toute l'application :
 * - Envoi de réponses JSON formatées
 * - Lecture et parsing des requêtes HTTP
 * - Validation de données (chaînes, nombres, email, téléphone, mot de passe)
 * 
 * Ces méthodes sont toutes statiques pour un accès facile depuis n'importe
 * quel contrôleur ou service de l'application.
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

class Utilitaires {

    /**
     * Envoie une réponse JSON et termine le script
     * 
     * Configure automatiquement les en-têtes HTTP appropriés
     * et encode les données en JSON avec support UTF-8.
     * 
     * @param mixed $donnees Données à encoder en JSON (tableau, objet, etc.)
     * @param int $statut Code de statut HTTP (200 par défaut)
     * @return void Termine l'exécution du script
     * @example Utilitaires::envoyerJSON(['succes' => true, 'message' => 'OK']);
     */
    public static function envoyerJSON($donnees, int $statut = 200): void {
        http_response_code($statut);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Lit et décode le corps JSON d'une requête HTTP
     * 
     * Utilisé pour récupérer les données envoyées en POST/PUT avec
     * Content-Type: application/json.
     * 
     * @return array Données décodées ou tableau vide si échec/vide
     * @example $donnees = Utilitaires::lireCorpsJSON();
     */
    public static function lireCorpsJSON(): array {
        $contenuBrut = file_get_contents('php://input');
        if (!$contenuBrut) return [];
        
        $donnees = json_decode($contenuBrut, true);
        return is_array($donnees) ? $donnees : [];
    }

    /**
     * Valide qu'une chaîne est non vide et respecte une longueur maximale
     * 
     * @param string|null $chaine Chaîne à valider
     * @param int $longueurMax Longueur maximale autorisée (50 par défaut)
     * @return bool True si la chaîne est valide (non vide et longueur OK)
     */
    public static function chaineValide(?string $chaine, int $longueurMax = 50): bool {
        return is_string($chaine) && $chaine !== '' && mb_strlen($chaine) <= $longueurMax;
    }

    /**
     * Valide qu'un nombre entier est compris entre deux bornes (inclusives)
     * 
     * @param mixed $nombre Valeur à valider
     * @param int $min Valeur minimale autorisée
     * @param int $max Valeur maximale autorisée
     * @return bool True si le nombre est un entier valide dans la plage
     */
    public static function entierEntre($nombre, int $min, int $max): bool {
        return filter_var($nombre, FILTER_VALIDATE_INT) !== false && $nombre >= $min && $nombre <= $max;
    }

    /**
     * Valide qu'un nombre décimal est supérieur ou égal à une valeur minimale
     * 
     * @param mixed $nombre Valeur à valider (peut être string, int ou float)
     * @param float $min Valeur minimale autorisée
     * @return bool True si le nombre est un décimal valide >= min
     */
    public static function nombreMinimum($nombre, float $min): bool {
        return filter_var($nombre, FILTER_VALIDATE_FLOAT) !== false && $nombre >= $min;
    }

    /**
     * Valide le format d'une adresse email
     * 
     * Utilise le filtre PHP natif FILTER_VALIDATE_EMAIL.
     * 
     * @param mixed $email Adresse email à valider
     * @return bool True si l'email a un format valide
     */
    public static function emailValide($email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valide le format d'un numéro de téléphone
     * 
     * Validation basique : autorise chiffres, espaces, +, (), -, .
     * Minimum 6 caractères.
     * 
     * @param mixed $telephone Numéro de téléphone à valider
     * @return bool True si le format est acceptable
     */
    public static function telephoneValide($telephone): bool {
        return preg_match('/^[0-9 +().-]{6,}$/', (string)$telephone) === 1;
    }

    /**
     * Valide la complexité d'un mot de passe
     * 
     * Critères : minimum 8 caractères, au moins une minuscule,
     * une majuscule et un chiffre.
     * 
     * @param string $motDePasse Mot de passe à valider
     * @return bool True si le mot de passe est suffisamment fort
     */
    public static function motDePasseFort($motDePasse): bool {
        return strlen($motDePasse) >= 8 
            && preg_match('/[a-z]/', $motDePasse) 
            && preg_match('/[A-Z]/', $motDePasse) 
            && preg_match('/\d/', $motDePasse);
    }

    /**
     * Récupère l'adresse IP publique du client
     * 
     * Vérifie les headers de proxy (X-Forwarded-For, etc.) avant
     * de fallback sur REMOTE_ADDR pour obtenir l'IP réelle.
     * 
     * Note: En environnement de développement local (127.0.0.1, ::1),
     * l'IP retournée sera localhost. Pour les logs de production,
     * configurez votre reverse proxy pour transmettre X-Forwarded-For.
     * 
     * @param bool $accepterLocale Si false, retourne 'localhost' au lieu de 127.0.0.1 (utile pour logs)
     * @return string Adresse IP du client
     */
    public static function obtenirIpClient(bool $accepterLocale = true): string {
        // Liste des headers à vérifier (ordre de priorité)
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Proxy standard
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For peut contenir plusieurs IPs (client, proxy1, proxy2...)
                // On prend la première (IP du client original)
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Valider que c'est une IP valide
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    // Normaliser localhost IPv6 en IPv4 pour cohérence
                    if ($ip === '::1') {
                        $ip = '127.0.0.1';
                    }
                    
                    // En développement, on peut vouloir différencier localhost dans les logs
                    if (!$accepterLocale && self::estIpLocale($ip)) {
                        return 'localhost';
                    }
                    
                    return $ip;
                }
            }
        }

        return 'unknown';
    }

    /**
     * Vérifie si une adresse IP est locale (localhost, réseau privé)
     * 
     * @param string $ip Adresse IP à vérifier
     * @return bool True si l'IP est locale
     */
    public static function estIpLocale(string $ip): bool {
        // IPs locales explicites
        $ipLocales = ['127.0.0.1', '::1', 'localhost'];
        if (in_array($ip, $ipLocales)) {
            return true;
        }
        
        // Vérifier les plages d'IP privées (RFC 1918)
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Génère un paramètre de version pour le cache busting des assets
     * @param string $cheminFichier Chemin relatif du fichier
     * @return string Timestamp actuel pour forcer le rechargement
     */
    public static function versionAsset(string $cheminFichier): string {
        return (string) time();
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════════════════
     * GESTION AVATAR PAR DÉFAUT CENTRALISÉE
     * ═══════════════════════════════════════════════════════════════════════════
     */
    
    /**
     * Chemin vers l'avatar par défaut SVG
     */
    const AVATAR_PAR_DEFAUT = 'assets/images/avatar-default.svg';
    
    /**
     * Retourne le chemin de l'avatar à utiliser
     * Si l'utilisateur a un avatar, retourne son chemin, sinon retourne l'avatar par défaut
     * 
     * @param string|null $avatarPath Chemin de l'avatar de l'utilisateur
     * @return string Chemin de l'avatar à utiliser
     */
    public static function obtenirAvatar(?string $avatarPath): string {
        if (!empty($avatarPath)) {
            return $avatarPath;
        }
        return self::AVATAR_PAR_DEFAUT;
    }
    
    /**
     * Génère le HTML complet pour afficher un avatar
     * 
     * @param string|null $avatarPath Chemin de l'avatar de l'utilisateur
     * @param string $classe Classe CSS à appliquer
     * @param string $alt Texte alternatif
     * @return string HTML de l'image avatar
     */
    public static function genererAvatarHTML(?string $avatarPath, string $classe = 'avatar', string $alt = 'Avatar'): string {
        $src = self::obtenirAvatar($avatarPath);
        return sprintf(
            '<img class="%s" src="%s" alt="%s">',
            htmlspecialchars($classe),
            htmlspecialchars($src),
            htmlspecialchars($alt)
        );
    }
}
