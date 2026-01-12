<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * GESTIONNAIRE DE LIMITATION DE TENTATIVES (VERSION BASE DE DONNÉES)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère le rate limiting des actions sensibles (connexion, upload, etc.)
 * Utilise la base de données au lieu de fichiers pour plus de fiabilité.
 * 
 * Pour la connexion : les tentatives sont liées à la COMBINAISON IP + email.
 * Cela empêche :
 * - Le brute force (limite par IP sur un compte)
 * - Le blocage malveillant de comptes (un attaquant ne peut pas bloquer
 *   un compte pour les autres utilisateurs, seulement depuis sa propre IP)
 * 
 * Nettoyage automatique : les entrées expirées sont supprimées périodiquement.
 * 
 * @author  Équipe ReVente-Auto
 * @version 3.0 - Version Base de Données
 * ═══════════════════════════════════════════════════════════════════════════
 */
class GestionnaireLimiteTaux {
    
    /**
     * Configuration des limites par type d'action
     */
    private const CONFIG = [
        'login' => ['max' => 5, 'temps' => 900],              // 5 essais / 15 min (lié à IP + compte)
        'upload' => ['max' => 50, 'temps' => 3600],           // 50 uploads / 1 heure (lié à l'IP)
        'password_reset' => ['max' => 1, 'temps' => 30],      // 1 demande / 30 secondes (lié à l'IP)
        'email_verification' => ['max' => 1, 'temps' => 30],  // 1 demande / 30 secondes (lié à l'IP)
        'email_change' => ['max' => 1, 'temps' => 60],        // 1 demande / 60 secondes (lié à l'IP)
        'vehicle_creation' => ['max' => 10, 'temps' => 3600], // 10 annonces / 1 heure (lié à l'IP)
        'default' => ['max' => 10, 'temps' => 60]
    ];

    /**
     * Vérifie si une tentative est autorisée pour une action donnée
     * 
     * @param string $action Identifiant de l'action (ex: 'login')
     * @param string|null $identifiant Pour 'login', c'est l'email du compte ciblé
     * @return bool True si autorisé, False si bloqué
     */
    public static function verifierTentative($action = 'login', $identifiant = null) {
        $db = BaseDeDonnees::obtenirConnexion();
        $cle = self::genererCle($action, $identifiant);
        $config = self::CONFIG[$action] ?? self::CONFIG['default'];
        
        $stmt = $db->prepare("
            SELECT attempts, expires_at 
            FROM rate_limits 
            WHERE action = ? AND identifier = ?
        ");
        $stmt->execute([$action, $cle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Pas d'entrée = autorisé
        if (!$row) {
            return true;
        }
        
        // Si expiré, supprimer et autoriser
        if (strtotime($row['expires_at']) < time()) {
            $db->prepare("DELETE FROM rate_limits WHERE action = ? AND identifier = ?")->execute([$action, $cle]);
            // Nettoyer toutes les autres entrées expirées en même temps
            self::nettoyerEntreesExpirees();
            return true;
        }
        
        // Vérifier si sous la limite
        return $row['attempts'] < $config['max'];
    }

    /**
     * Enregistre une tentative échouée
     * 
     * @param string $action Identifiant de l'action
     * @param string|null $identifiant Pour 'login', c'est l'email du compte ciblé
     * @param int $nombre Nombre de tentatives à ajouter (par défaut 1)
     * @return int Nombre de tentatives restantes
     */
    public static function ajouterTentative($action = 'login', $identifiant = null, $nombre = 1) {
        $db = BaseDeDonnees::obtenirConnexion();
        $cle = self::genererCle($action, $identifiant);
        $ip = self::obtenirIPReelle();
        $config = self::CONFIG[$action] ?? self::CONFIG['default'];
        $expiration = date('Y-m-d H:i:s', time() + $config['temps']);
        
        // Récupérer l'entrée existante
        $stmt = $db->prepare("
            SELECT id, attempts, expires_at 
            FROM rate_limits 
            WHERE action = ? AND identifier = ?
        ");
        $stmt->execute([$action, $cle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Si expiré, repartir de zéro
            if (strtotime($row['expires_at']) < time()) {
                $stmt = $db->prepare("
                    UPDATE rate_limits 
                    SET attempts = ?, expires_at = ?, ip_address = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $expiration, $ip, $row['id']]);
                $nouveauTotal = $nombre;
            } else {
                // Incrémenter
                $nouveauTotal = $row['attempts'] + $nombre;
                $stmt = $db->prepare("
                    UPDATE rate_limits 
                    SET attempts = ?, ip_address = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nouveauTotal, $ip, $row['id']]);
            }
        } else {
            // Nouvelle entrée
            $stmt = $db->prepare("
                INSERT INTO rate_limits (action, identifier, ip_address, attempts, expires_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$action, $cle, $ip, $nombre, $expiration]);
            $nouveauTotal = $nombre;
        }
        
        return max(0, $config['max'] - $nouveauTotal);
    }

    /**
     * Réinitialise le compteur après un succès
     * 
     * @param string $action Identifiant de l'action
     * @param string|null $identifiant Pour 'login', c'est l'email du compte ciblé
     */
    public static function reinitialiser($action = 'login', $identifiant = null) {
        $db = BaseDeDonnees::obtenirConnexion();
        $cle = self::genererCle($action, $identifiant);
        
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE action = ? AND identifier = ?");
        $stmt->execute([$action, $cle]);
    }

    /**
     * Obtient le temps restant avant déblocage (en secondes)
     * 
     * @param string $action Identifiant de l'action
     * @param string|null $identifiant Pour 'login', c'est l'email du compte ciblé
     * @return int Secondes restantes (0 si pas bloqué)
     */
    public static function obtenirTempsRestant($action = 'login', $identifiant = null) {
        $db = BaseDeDonnees::obtenirConnexion();
        $cle = self::genererCle($action, $identifiant);
        $config = self::CONFIG[$action] ?? self::CONFIG['default'];
        
        $stmt = $db->prepare("
            SELECT attempts, expires_at 
            FROM rate_limits 
            WHERE action = ? AND identifier = ?
        ");
        $stmt->execute([$action, $cle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return 0;
        }
        
        $expiresAt = strtotime($row['expires_at']);
        
        if ($row['attempts'] >= $config['max'] && time() < $expiresAt) {
            return $expiresAt - time();
        }
        
        return 0;
    }

    /**
     * Nettoie toutes les entrées expirées
     */
    private static function nettoyerEntreesExpirees() {
        try {
            $db = BaseDeDonnees::obtenirConnexion();
            $maintenant = date('Y-m-d H:i:s');
            $stmt = $db->prepare("DELETE FROM rate_limits WHERE expires_at < ?");
            $stmt->execute([$maintenant]);
        } catch (Exception $e) {
            // Ignorer les erreurs de nettoyage
        }
    }

    /**
     * Génère une clé unique pour identifier la combinaison action/IP/identifiant
     * 
     * Pour 'login' : IP + email du compte ciblé
     * Pour les autres : IP uniquement
     * 
     * @param string $action Type d'action
     * @param string|null $identifiant Email pour login, null pour les autres
     * @return string Clé unique (hash MD5)
     */
    private static function genererCle($action, $identifiant = null) {
        $ip = self::obtenirIPReelle();
        
        if ($action === 'login' && $identifiant !== null) {
            // Pour la connexion, on combine IP + email du compte ciblé
            $email = strtolower(trim($identifiant));
            $cle = $ip . '_' . $email;
        } else {
            // Pour les autres actions, on utilise uniquement l'IP
            $cle = $ip;
        }
        
        // Hash pour uniformiser la longueur
        return md5($action . '_' . $cle);
    }
    
    /**
     * Obtient l'IP réelle de l'utilisateur en tenant compte des proxys/CDN
     * 
     * @return string L'adresse IP réelle
     */
    private static function obtenirIPReelle() {
        // PRIORITÉ 1: Chercher l'IP publique dans les headers de proxy/CDN
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_REAL_IP',           // Nginx proxy
            'HTTP_X_FORWARDED_FOR',     // Proxy standard
            'HTTP_CLIENT_IP'            // Proxy alternatif
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Si HTTP_X_FORWARDED_FOR contient plusieurs IPs, prendre la première (IP du client)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                
                // Valider que c'est une IP valide ET publique (pas privée)
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        // PRIORITÉ 2: Utiliser REMOTE_ADDR (IP directe du client)
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Normaliser localhost IPv6 en IPv4 pour cohérence (développement local uniquement)
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }
        
        return $ip;
    }
    
    /**
     * Obtenir les statistiques de rate limiting (pour admin)
     * 
     * @return array Statistiques
     */
    public static function obtenirStatistiques() {
        $db = BaseDeDonnees::obtenirConnexion();
        
        $stmt = $db->query("
            SELECT action, COUNT(*) as count, SUM(attempts) as total_attempts
            FROM rate_limits 
            WHERE expires_at > NOW()
            GROUP BY action
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Débloquer manuellement une IP/identifiant (pour admin)
     * 
     * @param string $action Type d'action
     * @param string $ip Adresse IP à débloquer
     * @return bool Succès
     */
    public static function debloquerIP($action, $ip) {
        $db = BaseDeDonnees::obtenirConnexion();
        
        // Supprimer toutes les entrées pour cette IP et action
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE action = ? AND ip_address = ?");
        return $stmt->execute([$action, $ip]);
    }
}
