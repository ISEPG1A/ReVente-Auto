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
     * Génère une paire de clés RSA
     */
    public static function genererPaireCles() {
        $config = array(
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privKey);
        $pubKeyDetails = openssl_pkey_get_details($res);
        $pubKey = $pubKeyDetails["key"];
        
        return ['public' => $pubKey, 'private' => $privKey];
    }
}
