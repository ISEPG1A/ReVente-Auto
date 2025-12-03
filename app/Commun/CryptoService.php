<?php
/**
 * Service de cryptographie hybride (RSA + AES)
 * 
 * Principe :
 * 1. Le message est chiffré avec une clé symétrique aléatoire (AES-256-CBC).
 * 2. Cette clé symétrique est chiffrée avec la clé publique RSA du destinataire.
 * 3. On stocke : le message chiffré, l'IV, et la clé symétrique chiffrée.
 * 
 * Pour déchiffrer :
 * 1. On déchiffre la clé symétrique avec la clé privée RSA de l'utilisateur.
 * 2. On utilise cette clé symétrique pour déchiffrer le message.
 */

class CryptoService {

    /**
     * Récupère la clé secrète de l'application depuis la configuration
     */
    private static function obtenirCleSecrete() {
        static $cle = null;
        if ($cle === null) {
            $configPath = __DIR__ . '/../../config.php';
            if (file_exists($configPath)) {
                $config = require $configPath;
                $cle = $config['app_secret_key'] ?? null;
            }
        }
        if (!$cle || strlen($cle) !== 64) {
            // Fallback ou erreur si la clé n'est pas configurée correctement
            // Pour ce projet, on peut lancer une exception
            throw new Exception("Clé secrète d'application manquante ou invalide (doit faire 64 caractères hexadécimaux).");
        }
        return hex2bin($cle);
    }

    /**
     * Chiffre une donnée sensible (ex: clé privée) pour stockage en base
     * Utilise AES-256-CBC avec la clé secrète de l'application
     */
    public static function chiffrerDonnee($donneeClair) {
        $cle = self::obtenirCleSecrete();
        $ivlen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        $chiffre = openssl_encrypt($donneeClair, 'aes-256-cbc', $cle, 0, $iv);
        
        // On retourne IV + Message chiffré concaténés et encodés en base64
        // Format : base64(iv . chiffre)
        return base64_encode($iv . $chiffre);
    }

    /**
     * Déchiffre une donnée sensible
     */
    public static function dechiffrerDonnee($donneeChiffreeBase64) {
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
     * Chiffre un message pour un destinataire ET l'expéditeur (pour qu'il puisse le relire)
     */
    public static function chiffrerMessagePourDeux($messageClair, $clePubliqueDestinataire, $clePubliqueExpediteur) {
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
     */
    public static function dechiffrerMessage($messageChiffre, $ivBase64, $cleSymetriqueChiffreeBase64, $clePriveeUtilisateur) {
        $iv = base64_decode($ivBase64);
        $cleSymetriqueChiffree = base64_decode($cleSymetriqueChiffreeBase64);
        
        // 1. Déchiffrer la clé symétrique avec RSA (Clé privée)
        $cleSymetrique = '';
        if (!openssl_private_decrypt($cleSymetriqueChiffree, $cleSymetrique, $clePriveeUtilisateur)) {
            // Si échec, c'est peut-être que le message a été envoyé par soi-même (et chiffré avec la clé publique de l'autre)
            // Dans un vrai système E2EE, on chiffrerait aussi une copie pour l'expéditeur.
            // Ici, pour simplifier, si on est l'expéditeur, on ne peut pas relire ses propres messages envoyés 
            // SAUF si on a stocké une version chiffrée pour soi-même.
            // Pour ce prototype, on accepte cette limitation ou on gère le double chiffrement plus tard.
            return "[Message chiffré impossible à lire]";
        }

        // 2. Déchiffrer le message avec AES
        $messageClair = openssl_decrypt($messageChiffre, 'aes-256-cbc', $cleSymetrique, 0, $iv);
        
        return $messageClair;
    }

    /**
     * Hache un mot de passe en utilisant l'algorithme par défaut (BCRYPT)
     */
    public static function hacherMotDePasse($motDePasse) {
        return password_hash($motDePasse, PASSWORD_BCRYPT);
    }

    /**
     * Vérifie si un mot de passe correspond au hachage
     */
    public static function verifierMotDePasse($motDePasse, $hachage) {
        return password_verify($motDePasse, $hachage);
    }

    /**
     * Génère un token aléatoire sécurisé
     */
    public static function genererToken($longueur = 48) {
        return rtrim(strtr(base64_encode(random_bytes($longueur)), '+/', '-_'), '=');
    }

    /**
     * Génère un code numérique aléatoire
     */
    public static function genererCode($longueur = 6) {
        $min = 10 ** ($longueur - 1);
        $max = (10 ** $longueur) - 1;
        return (string)random_int($min, $max);
    }

    /**
     * Génère une paire de clés RSA
     */
    public static function genererPaireCles() {
        $config = array(
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
            // Configuration spécifique pour XAMPP sur Windows
            "config" => "C:/xampp/php/extras/ssl/openssl.cnf"
        );
        
        $res = openssl_pkey_new($config);
        
        if ($res === false) {
            $erreurs = "";
            while ($msg = openssl_error_string()) {
                $erreurs .= $msg . "; ";
            }
            throw new Exception("Échec de la génération des clés RSA : " . $erreurs);
        }

        // On passe aussi la config à export pour qu'il trouve le fichier openssl.cnf
        if (!openssl_pkey_export($res, $privKey, null, $config)) {
             throw new Exception("Échec de l'export de la clé privée.");
        }

        $pubKeyDetails = openssl_pkey_get_details($res);
        if (!$pubKeyDetails) {
            throw new Exception("Échec de la récupération de la clé publique.");
        }
        $pubKey = $pubKeyDetails["key"];
        
        return ['public' => $pubKey, 'private' => $privKey];
    }
}
