<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SERVICE DE CHIFFREMENT HYBRIDE (RSA + AES)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce service gère toutes les opérations cryptographiques de l'application :
 * - Chiffrement/déchiffrement des données sensibles (AES-256-CBC)
 * - Chiffrement de bout en bout pour la messagerie (RSA + AES hybride)
 * - Hachage sécurisé des mots de passe (BCRYPT)
 * - Génération de tokens et codes aléatoires
 * - Génération de paires de clés RSA
 * 
 * Principe du chiffrement hybride pour la messagerie :
 * 1. Le message est chiffré avec une clé symétrique aléatoire (AES-256-CBC)
 * 2. Cette clé symétrique est chiffrée avec la clé publique RSA du destinataire
 * 3. On stocke : le message chiffré, l'IV, et la clé symétrique chiffrée
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @since   2024
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ServiceChiffrement {

    /**
     * Récupère la clé secrète de l'application depuis la configuration
     * 
     * Cette clé est utilisée pour chiffrer les données sensibles stockées
     * en base de données (comme les clés privées RSA des utilisateurs).
     * 
     * @throws Exception Si la clé n'est pas configurée ou est invalide
     * @return string Clé binaire de 32 octets (256 bits)
     */
    private static function obtenirCleSecrete(): string {
        static $cle = null;
        if ($cle === null) {
            $configPath = __DIR__ . '/../../config.php';
            if (file_exists($configPath)) {
                $config = require $configPath;
                $cle = $config['app_secret_key'] ?? null;
            }
        }
        
        // Vérification que la clé fait bien 64 caractères hexadécimaux (32 octets)
        if (!$cle || strlen($cle) !== 64) {
            throw new Exception("Clé secrète d'application manquante ou invalide (doit faire 64 caractères hexadécimaux).");
        }
        
        // Conversion de l'hexadécimal en binaire
        return hex2bin($cle);
    }

    /**
     * Chiffre une donnée sensible pour stockage en base de données
     * 
     * Utilise AES-256-CBC avec la clé secrète de l'application.
     * Format de sortie : base64(IV + données_chiffrées)
     * 
     * @param string $donneeClair Donnée à chiffrer
     * @return string Donnée chiffrée encodée en base64
     */
    public static function chiffrerDonnee(string $donneeClair): string {
        $cle = self::obtenirCleSecrete();
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        $chiffre = openssl_encrypt($donneeClair, 'aes-256-cbc', $cle, 0, $iv);
        
        // On retourne IV + Message chiffré concaténés et encodés en base64
        return base64_encode($iv . $chiffre);
    }

    /**
     * Déchiffre une donnée sensible stockée en base de données
     * 
     * @param string $donneeChiffreeBase64 Donnée chiffrée encodée en base64
     * @return string|false Donnée en clair ou false si échec
     */
    public static function dechiffrerDonnee(string $donneeChiffreeBase64) {
        $cle = self::obtenirCleSecrete();
        $raw = base64_decode($donneeChiffreeBase64);
        
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        
        if (strlen($raw) < $ivlen) {
            return false; // Donnée invalide
        }
        
        $iv = substr($raw, 0, $ivlen);
        $chiffre = substr($raw, $ivlen);
        
        return openssl_decrypt($chiffre, 'aes-256-cbc', $cle, 0, $iv);
    }

    /**
     * Chiffre un message pour deux destinataires (expéditeur + destinataire)
     * 
     * Permet à l'expéditeur de relire ses propres messages envoyés.
     * Utilise le chiffrement hybride RSA+AES pour une sécurité optimale.
     * 
     * @param string $messageClair Message en texte clair à chiffrer
     * @param string $clePubliqueDestinataire Clé publique RSA du destinataire
     * @param string $clePubliqueExpediteur Clé publique RSA de l'expéditeur
     * @throws Exception Si le chiffrement échoue
     * @return array Tableau contenant le message chiffré et les clés
     */
    public static function chiffrerMessagePourDeux(
        string $messageClair, 
        string $clePubliqueDestinataire, 
        string $clePubliqueExpediteur
    ): array {
        // 1. Générer une clé symétrique aléatoire (32 bytes pour AES-256)
        $cleSymetrique = openssl_random_pseudo_bytes(32);
        
        // 2. Générer un IV (Vecteur d'initialisation)
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        // 3. Chiffrer le message avec AES
        $messageChiffre = openssl_encrypt($messageClair, 'aes-256-cbc', $cleSymetrique, 0, $iv);
        
        // 4a. Chiffrer la clé symétrique pour le DESTINATAIRE
        $clePourDest = '';
        if (!openssl_public_encrypt($cleSymetrique, $clePourDest, $clePubliqueDestinataire)) {
            throw new Exception("Erreur chiffrement clé destinataire.");
        }

        // 4b. Chiffrer la clé symétrique pour l'EXPÉDITEUR
        $clePourExp = '';
        if (!openssl_public_encrypt($cleSymetrique, $clePourExp, $clePubliqueExpediteur)) {
            throw new Exception("Erreur chiffrement clé expéditeur.");
        }

        return [
            'content' => $messageChiffre,
            'iv' => base64_encode($iv),
            'encrypted_key_recipient' => base64_encode($clePourDest),
            'encrypted_key_sender' => base64_encode($clePourExp)
        ];
    }

    /**
     * Déchiffre un message avec la clé privée de l'utilisateur
     * 
     * @param string $messageChiffre Message chiffré (AES)
     * @param string $ivBase64 Vecteur d'initialisation en base64
     * @param string $cleSymetriqueChiffreeBase64 Clé symétrique chiffrée en base64
     * @param string $clePriveeUtilisateur Clé privée RSA de l'utilisateur
     * @return string Message déchiffré ou message d'erreur
     */
    public static function dechiffrerMessage(
        string $messageChiffre, 
        string $ivBase64, 
        string $cleSymetriqueChiffreeBase64, 
        string $clePriveeUtilisateur
    ): string {
        $iv = base64_decode($ivBase64);
        $cleSymetriqueChiffree = base64_decode($cleSymetriqueChiffreeBase64);
        
        // 1. Déchiffrer la clé symétrique avec RSA (Clé privée)
        $cleSymetrique = '';
        if (!openssl_private_decrypt($cleSymetriqueChiffree, $cleSymetrique, $clePriveeUtilisateur)) {
            // Échec du déchiffrement - le message n'est pas destiné à cet utilisateur
            // ou la clé privée est incorrecte
            return "[Message chiffré impossible à lire]";
        }

        // 2. Déchiffrer le message avec la clé symétrique AES
        $messageClair = openssl_decrypt($messageChiffre, 'aes-256-cbc', $cleSymetrique, 0, $iv);
        
        return $messageClair;
    }

    /**
     * Hache un mot de passe de manière sécurisée
     * 
     * Utilise l'algorithme BCRYPT avec un coût automatique.
     * 
     * @param string $motDePasse Mot de passe en clair
     * @return string Mot de passe haché
     */
    public static function hacherMotDePasse(string $motDePasse): string {
        return password_hash($motDePasse, PASSWORD_BCRYPT);
    }

    /**
     * Vérifie si un mot de passe correspond à son hachage
     * 
     * @param string $motDePasse Mot de passe en clair à vérifier
     * @param string $hachage Hachage stocké en base de données
     * @return bool True si le mot de passe est correct
     */
    public static function verifierMotDePasse(string $motDePasse, string $hachage): bool {
        return password_verify($motDePasse, $hachage);
    }

    /**
     * Génère un token aléatoire sécurisé (URL-safe)
     * 
     * Utilisé pour les tokens de réinitialisation de mot de passe,
     * les tokens de session, etc.
     * 
     * @param int $longueur Nombre d'octets aléatoires (48 par défaut = 64 caractères)
     * @return string Token encodé en base64 URL-safe
     */
    public static function genererToken(int $longueur = 48): string {
        return rtrim(strtr(base64_encode(random_bytes($longueur)), '+/', '-_'), '=');
    }

    /**
     * Génère un code numérique aléatoire
     * 
     * Utilisé pour les codes de vérification par email/SMS.
     * 
     * @param int $longueur Nombre de chiffres (6 par défaut)
     * @return string Code numérique
     */
    public static function genererCode(int $longueur = 6): string {
        $min = 10 ** ($longueur - 1);
        $max = (10 ** $longueur) - 1;
        return (string)random_int($min, $max);
    }

    /**
     * Génère une paire de clés RSA (publique + privée)
     * 
     * Utilisé lors de l'inscription d'un utilisateur pour créer
     * ses clés de chiffrement de bout en bout (E2EE).
     * 
     * @throws Exception Si la génération échoue
     * @return array ['public' => clé_publique, 'private' => clé_privée]
     */
    public static function genererPaireCles(): array {
        // Configuration de la génération RSA
        $configuration = [
            "digest_alg" => "sha256",           // Algorithme de hachage
            "private_key_bits" => 2048,         // Taille de la clé (sécurité standard)
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        ];
        
        // Tentative de détection automatique du fichier openssl.cnf
        $cheminsOpenSSL = [
            "C:/xampp/php/extras/ssl/openssl.cnf",  // XAMPP Windows
            "C:/wamp64/bin/php/php*/extras/ssl/openssl.cnf", // WAMP Windows
            "/usr/lib/ssl/openssl.cnf",              // Linux Ubuntu/Debian
            "/etc/ssl/openssl.cnf",                  // Linux générique
            "/etc/pki/tls/openssl.cnf",              // Linux Red Hat/CentOS
            "/usr/local/ssl/openssl.cnf",            // macOS/BSD
        ];
        
        // Chercher un fichier de configuration existant
        foreach ($cheminsOpenSSL as $chemin) {
            // Support du wildcard pour WAMP
            if (strpos($chemin, '*') !== false) {
                $fichiers = glob($chemin);
                if (!empty($fichiers) && file_exists($fichiers[0])) {
                    $configuration["config"] = $fichiers[0];
                    break;
                }
            } elseif (file_exists($chemin)) {
                $configuration["config"] = $chemin;
                break;
            }
        }
        
        // Si aucun fichier trouvé, essayer sans config explicite (PHP devrait le détecter)
        // Note : La plupart des installations PHP modernes peuvent détecter openssl.cnf automatiquement
        
        // Génération de la paire de clés
        $ressource = openssl_pkey_new($configuration);
        
        if ($ressource === false) {
            // Collecte des erreurs OpenSSL pour le débogage
            $erreurs = "";
            while ($message = openssl_error_string()) {
                $erreurs .= $message . "; ";
            }
            
            // Log pour diagnostic
            error_log("Échec génération RSA. Config utilisée : " . json_encode($configuration));
            error_log("OPENSSL_CONF env : " . (getenv('OPENSSL_CONF') ?: 'non défini'));
            
            throw new Exception("Échec de la génération des clés RSA : " . $erreurs);
        }

        // Export de la clé privée au format PEM
        if (!openssl_pkey_export($ressource, $clePrivee, null, $configuration)) {
            throw new Exception("Échec de l'export de la clé privée.");
        }

        // Extraction de la clé publique
        $detailsClePublique = openssl_pkey_get_details($ressource);
        if (!$detailsClePublique) {
            throw new Exception("Échec de la récupération de la clé publique.");
        }
        $clePublique = $detailsClePublique["key"];
        
        return [
            'public' => $clePublique, 
            'private' => $clePrivee
        ];
    }
}
