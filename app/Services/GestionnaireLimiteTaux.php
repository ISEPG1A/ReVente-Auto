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
    
    // ═══════════════════════════════════════════════════════════════════════════
    // COOLDOWN CGU - GÉNÉRATION AUTOMATIQUE DES PDFs
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * Durée du cooldown CGU en secondes (1 heure)
     */
    public const DUREE_COOLDOWN_CGU = 3600;
    
    /**
     * Chemin du fichier de cache pour le cooldown CGU
     */
    private static function obtenirCheminCacheCGU(): string {
        return dirname(__DIR__, 2) . '/public/uploads/cgu_versions/cgu_cooldown.json';
    }
    
    /**
     * Démarre ou redémarre le cooldown après une modification des CGU
     * 
     * @param string $typeModification Type de modification (article, section, point)
     * @param int|null $elementId ID de l'élément modifié
     * @return bool Succès de l'opération
     */
    public static function demarrerCooldownCGU(string $typeModification = '', ?int $elementId = null): bool {
        $fichier = self::obtenirCheminCacheCGU();
        $cooldownActuel = self::lireCooldownCGU();
        
        $donnees = [
            'timestamp_debut' => $cooldownActuel['timestamp_debut'] ?? time(),
            'timestamp_expiration' => time() + self::DUREE_COOLDOWN_CGU,
            'derniere_modification' => [
                'type' => $typeModification,
                'element_id' => $elementId,
                'date' => date('Y-m-d H:i:s')
            ],
            'nombre_modifications' => ($cooldownActuel['nombre_modifications'] ?? 0) + 1
        ];
        
        // S'assurer que le dossier existe
        $dossier = dirname($fichier);
        if (!is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }
        
        return file_put_contents($fichier, json_encode($donnees, JSON_PRETTY_PRINT), LOCK_EX) !== false;
    }
    
    /**
     * Lit les données du cooldown CGU depuis le fichier cache
     * 
     * @return array|null Données du cooldown ou null si inexistant
     */
    private static function lireCooldownCGU(): ?array {
        $fichier = self::obtenirCheminCacheCGU();
        
        if (!file_exists($fichier)) {
            return null;
        }
        
        $contenu = file_get_contents($fichier);
        if ($contenu === false) {
            return null;
        }
        
        $donnees = json_decode($contenu, true);
        return is_array($donnees) ? $donnees : null;
    }
    
    /**
     * Vérifie si un cooldown CGU est actuellement actif
     * 
     * @return bool True si un cooldown est actif
     */
    public static function estCooldownCGUActif(): bool {
        $cooldown = self::lireCooldownCGU();
        
        if ($cooldown === null) {
            return false;
        }
        
        return time() < $cooldown['timestamp_expiration'];
    }
    
    /**
     * Obtient les informations sur le cooldown CGU actuel
     * 
     * @return array|null Informations ou null si pas de cooldown
     */
    public static function obtenirInfosCooldownCGU(): ?array {
        $cooldown = self::lireCooldownCGU();
        
        if ($cooldown === null) {
            return null;
        }
        
        $tempsRestant = max(0, $cooldown['timestamp_expiration'] - time());
        $estActif = $tempsRestant > 0;
        
        return [
            'actif' => $estActif,
            'temps_restant_secondes' => $tempsRestant,
            'temps_restant_formate' => self::formaterTempsCooldown($tempsRestant),
            'debut' => date('d/m/Y H:i:s', $cooldown['timestamp_debut']),
            'expiration' => date('d/m/Y H:i:s', $cooldown['timestamp_expiration']),
            'nombre_modifications' => $cooldown['nombre_modifications'] ?? 1,
            'derniere_modification' => $cooldown['derniere_modification'] ?? null
        ];
    }
    
    /**
     * Formate un temps en secondes en format lisible
     * 
     * @param int $secondes Temps en secondes
     * @return string Temps formaté (ex: "45min 30s")
     */
    private static function formaterTempsCooldown(int $secondes): string {
        if ($secondes <= 0) {
            return '0s';
        }
        
        $heures = floor($secondes / 3600);
        $minutes = floor(($secondes % 3600) / 60);
        $secs = $secondes % 60;
        
        $parties = [];
        if ($heures > 0) {
            $parties[] = "{$heures}h";
        }
        if ($minutes > 0) {
            $parties[] = "{$minutes}min";
        }
        if ($secs > 0 || empty($parties)) {
            $parties[] = "{$secs}s";
        }
        
        return implode(' ', $parties);
    }
    
    /**
     * Vérifie si le cooldown CGU est expiré et génère le PDF si nécessaire
     * 
     * @return array Résultat de la vérification
     */
    public static function verifierEtGenererPDFCGU(): array {
        $cooldown = self::lireCooldownCGU();
        
        // Pas de cooldown actif
        if ($cooldown === null) {
            return [
                'action' => 'none',
                'message' => 'Aucun cooldown actif',
                'version' => null
            ];
        }
        
        // Cooldown encore actif
        if (time() < $cooldown['timestamp_expiration']) {
            $tempsRestant = $cooldown['timestamp_expiration'] - time();
            
            return [
                'action' => 'waiting',
                'message' => "Cooldown actif - " . self::formaterTempsCooldown($tempsRestant) . " restantes",
                'temps_restant' => $tempsRestant,
                'modifications' => $cooldown['nombre_modifications'] ?? 1,
                'version' => null
            ];
        }
        
        // Cooldown expiré - vérifier si le contenu a changé par rapport à la dernière version archivée
        $modeleVersion = new ModeleVersionCGU();
        
        // Supprimer le cooldown car il est traité
        self::supprimerCooldownCGU();
        
        // Vérifier si le contenu a changé par rapport à la dernière version archivée en BDD
        if (!$modeleVersion->contenuAChange()) {
            return [
                'action' => 'no_change',
                'message' => 'Cooldown expiré mais contenu identique à la dernière version archivée',
                'version' => null
            ];
        }
        
        // Générer la nouvelle version PDF
        $nouvelleVersion = $modeleVersion->creerNouvelleVersion();
        
        if ($nouvelleVersion) {
            // Log de la génération automatique
            if (class_exists('ModeleAdmin')) {
                $modeleAdmin = new ModeleAdmin();
                $modeleAdmin->ajouterLog('cgu', 'Version CGU générée automatiquement', [
                    'version' => $nouvelleVersion['id'] ?? null,
                    'nom_fichier' => $nouvelleVersion['nom_fichier'],
                    'taille' => $nouvelleVersion['taille_fichier'],
                    'modifications_pendant_cooldown' => $cooldown['nombre_modifications'] ?? 1
                ], null, null, null);
            }
            
            return [
                'action' => 'generated',
                'message' => 'Nouvelle version PDF générée avec succès',
                'version' => $nouvelleVersion
            ];
        }
        
        return [
            'action' => 'error',
            'message' => 'Erreur lors de la génération du PDF',
            'version' => null
        ];
    }
    
    /**
     * Supprime le fichier de cooldown CGU
     * 
     * @return bool Succès de la suppression
     */
    private static function supprimerCooldownCGU(): bool {
        $fichier = self::obtenirCheminCacheCGU();
        
        if (file_exists($fichier)) {
            return unlink($fichier);
        }
        
        return true;
    }
    
    /**
     * Annule le cooldown CGU actuel (admin uniquement)
     * 
     * @param bool $genererMaintenant Si true, génère le PDF immédiatement
     * @return array Résultat de l'opération
     */
    public static function annulerCooldownCGU(bool $genererMaintenant = false): array {
        $cooldown = self::lireCooldownCGU();
        
        if ($cooldown === null) {
            return [
                'ok' => true,
                'message' => 'Aucun cooldown à annuler'
            ];
        }
        
        self::supprimerCooldownCGU();
        
        if ($genererMaintenant) {
            $modeleVersion = new ModeleVersionCGU();
            
            if ($modeleVersion->contenuAChange()) {
                $version = $modeleVersion->creerNouvelleVersion();
                
                if ($version) {
                    return [
                        'ok' => true,
                        'message' => 'Cooldown annulé et PDF généré',
                        'version' => $version
                    ];
                }
                
                return [
                    'ok' => false,
                    'message' => 'Cooldown annulé mais erreur lors de la génération du PDF'
                ];
            }
            
            return [
                'ok' => true,
                'message' => 'Cooldown annulé - pas de changement à archiver'
            ];
        }
        
        return [
            'ok' => true,
            'message' => 'Cooldown annulé'
        ];
    }
}
