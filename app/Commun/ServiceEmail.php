<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SERVICE EMAIL - ENVOI D'EMAILS VIA SMTP
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce service gère l'envoi d'emails via SMTP (Gmail).
 * Utilise PHPMailer ou une implémentation native avec stream_socket_client.
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ServiceEmail {
    private static $config = null;

    /**
     * Charge la configuration SMTP depuis config.php
     */
    private static function chargerConfig() {
        if (self::$config === null) {
            $configPath = __DIR__ . '/../../config.php';
            if (file_exists($configPath)) {
                self::$config = require $configPath;
            } else {
                throw new Exception('Fichier de configuration introuvable.');
            }
        }
        return self::$config;
    }

    /**
     * Envoie un email via SMTP
     * 
     * @param string $destinataire Email du destinataire
     * @param string $sujet Sujet de l'email
     * @param string $corpsHTML Corps HTML de l'email
     * @param string $corpsTexte Corps texte brut (fallback)
     * @return bool True si envoyé avec succès
     * @throws Exception Si l'envoi échoue
     */
    public static function envoyer($destinataire, $sujet, $corpsHTML, $corpsTexte = '') {
        $config = self::chargerConfig();

        error_log('ServiceEmail::envoyer - Destinataire: ' . $destinataire);
        error_log('ServiceEmail::envoyer - Config SMTP Host: ' . ($config['smtp_host'] ?? 'NON DEFINI'));
        error_log('ServiceEmail::envoyer - Config SMTP User: ' . ($config['smtp_user'] ?? 'NON DEFINI'));
        
        // Vérifier que la configuration SMTP est complète
        if (empty($config['smtp_user']) || empty($config['smtp_pass'])) {
            error_log('ERREUR: Configuration SMTP incomplète');
            throw new Exception('Configuration SMTP incomplète.');
        }

        // Utiliser mail() natif avec configuration SMTP (via ini_set)
        // Ou utiliser une connexion socket directe pour plus de contrôle
        return self::envoyerViaSMTP(
            $config['smtp_host'],
            $config['smtp_port'],
            $config['smtp_user'],
            $config['smtp_pass'],
            $config['smtp_from_email'],
            $config['smtp_from_name'],
            $destinataire,
            $sujet,
            $corpsHTML,
            $corpsTexte
        );
    }

    /**
     * Envoie un email de vérification d'adresse
     * 
     * @param string $destinataire Email du destinataire
     * @param string $prenom Prénom de l'utilisateur
     * @param string $token Token de vérification
     * @return bool
     */
    public static function envoyerVerificationEmail($destinataire, $prenom, $token) {
        // Construire l'URL de vérification
        $urlVerification = self::obtenirUrlBase() . "/api/profil?action=verify-email&token=" . urlencode($token);

        $sujet = "Vérifiez votre adresse email - ReVente-Auto";
        
        $corpsHTML = self::genererTemplateVerificationEmail($prenom, $urlVerification);
        
        $corpsTexte = "Bonjour $prenom,\n\n" .
            "Merci de vous être inscrit sur ReVente-Auto !\n\n" .
            "Pour activer votre compte, veuillez cliquer sur le lien suivant :\n" .
            "$urlVerification\n\n" .
            "Ce lien est valide pendant 24 heures.\n\n" .
            "Si vous n'avez pas créé de compte, vous pouvez ignorer cet email.\n\n" .
            "Cordialement,\n" .
            "L'équipe ReVente-Auto";

        return self::envoyer($destinataire, $sujet, $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     * 
     * @param string $destinataire Email du destinataire
     * @param string $prenom Prénom de l'utilisateur
     * @param string $token Token de réinitialisation
     * @return bool
     */
    public static function envoyerResetMotDePasse($destinataire, $prenom, $token) {
        $urlReset = self::obtenirUrlBase() . "/api/auth/reset-password?action=reset-password&token=" . urlencode($token);

        $sujet = "Réinitialisation de votre mot de passe - ReVente-Auto";
        
        $corpsHTML = self::genererTemplateResetMotDePasse($prenom, $urlReset);
        
        $corpsTexte = "Bonjour $prenom,\n\n" .
            "Vous avez demandé la réinitialisation de votre mot de passe.\n\n" .
            "Pour créer un nouveau mot de passe, cliquez sur le lien suivant :\n" .
            "$urlReset\n\n" .
            "Ce lien est valide pendant 1 heure.\n\n" .
            "Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.\n\n" .
            "Cordialement,\n" .
            "L'équipe ReVente-Auto";

        return self::envoyer($destinataire, $sujet, $corpsHTML, $corpsTexte);
    }

    /**
     * Envoi via SMTP natif (sans PHPMailer)
     */
    private static function envoyerViaSMTP($host, $port, $username, $password, $fromEmail, $fromName, $to, $subject, $htmlBody, $textBody) {
        error_log('envoyerViaSMTP - Connexion à ' . $host . ':' . $port);
        
        // Créer la connexion socket
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            error_log('ERREUR: Connexion socket échouée - ' . $errstr . ' (' . $errno . ')');
            throw new Exception("Impossible de se connecter au serveur SMTP : $errstr ($errno)");
        }
        
        error_log('Connexion socket réussie');

        // Fonction helper pour lire la réponse
        $lireReponse = function() use ($socket) {
            return fgets($socket, 512);
        };

        // Fonction helper pour envoyer une commande
        $envoyerCommande = function($commande) use ($socket) {
            fwrite($socket, $commande . "\r\n");
        };

        try {
            // Lire le message de bienvenue
            $bienvenue = $lireReponse();
            error_log("Bienvenue SMTP: $bienvenue");

            // EHLO
            $envoyerCommande("EHLO " . $host);
            do {
                $reponse = $lireReponse();
                error_log("EHLO réponse: $reponse");
            } while (strpos($reponse, '-') === 3); // Continue tant qu'il y a des lignes multi-ligne

            // STARTTLS
            $envoyerCommande("STARTTLS");
            $reponse = $lireReponse();
            error_log("STARTTLS réponse: $reponse");
            
            // Vérifier que le serveur est prêt pour TLS
            if (strpos($reponse, '220') !== 0) {
                throw new Exception("Le serveur n'est pas prêt pour TLS: $reponse");
            }
            
            // Upgrade vers TLS (compatible Gmail - TLS 1.2 ou 1.3)
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            stream_set_blocking($socket, true); // S'assurer que le socket est bloquant
            
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                $error = error_get_last();
                throw new Exception("Échec de l'activation TLS: " . ($error['message'] ?? 'Erreur inconnue'));
            }
            
            error_log("TLS activé avec succès");

            // EHLO après TLS
            $envoyerCommande("EHLO " . $host);
            do {
                $reponse = $lireReponse();
                error_log("EHLO post-TLS réponse: $reponse");
            } while (strpos($reponse, '-') === 3);

            // AUTH LOGIN
            $envoyerCommande("AUTH LOGIN");
            $reponseAuthInit = $lireReponse();
            error_log("AUTH LOGIN réponse: $reponseAuthInit");

            $envoyerCommande(base64_encode($username));
            $reponseUser = $lireReponse();
            error_log("Username réponse: $reponseUser");

            $envoyerCommande(base64_encode($password));
            $reponseAuth = $lireReponse();
            error_log("Password réponse: $reponseAuth");
            
            if (strpos($reponseAuth, '235') === false) {
                throw new Exception("Échec de l'authentification SMTP: $reponseAuth");
            }
            
            error_log("Authentification réussie");

            // MAIL FROM
            $envoyerCommande("MAIL FROM:<{$fromEmail}>");
            $lireReponse();

            // RCPT TO
            $envoyerCommande("RCPT TO:<{$to}>");
            $lireReponse();

            // DATA
            $envoyerCommande("DATA");
            $lireReponse();

            // En-têtes et corps
            $boundary = "----=_Boundary_" . md5(uniqid());
            
            $message = "From: {$fromName} <{$fromEmail}>\r\n";
            $message .= "To: <{$to}>\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $message .= "\r\n";
            
            // Partie texte
            if (!empty($textBody)) {
                $message .= "--{$boundary}\r\n";
                $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $message .= "Content-Transfer-Encoding: 8bit\r\n";
                $message .= "\r\n";
                $message .= $textBody . "\r\n";
            }
            
            // Partie HTML
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 8bit\r\n";
            $message .= "\r\n";
            $message .= $htmlBody . "\r\n";
            
            $message .= "--{$boundary}--\r\n";
            $message .= ".\r\n";

            fwrite($socket, $message);
            $lireReponse();

            // QUIT
            $envoyerCommande("QUIT");
            $lireReponse();

            fclose($socket);
            return true;

        } catch (Exception $e) {
            @fclose($socket);
            throw $e;
        }
    }

    /**
     * Obtient l'URL de base de l'application
     */
    private static function obtenirUrlBase() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Extraire le chemin de base
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $basePath = str_replace('\\', '/', dirname($scriptName));
        
        // Si le script est dans /public/, remonter d'un niveau
        if (strpos($basePath, '/public') !== false) {
            $basePath = substr($basePath, 0, strpos($basePath, '/public'));
        }
        
        // Nettoyer le chemin
        $basePath = rtrim($basePath, '/');
        if ($basePath === '.') {
            $basePath = '';
        }
        
        return $protocol . '://' . $host . $basePath;
    }

    /**
     * Template HTML pour l'email de vérification
     */
    private static function genererTemplateVerificationEmail($prenom, $url) {
        return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifiez votre email</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f8fafc;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);">
                    <!-- En-tête -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -0.5px;">🚗 ReVente-Auto</h1>
                        </td>
                    </tr>
                    
                    <!-- Contenu -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #0f172a; margin: 0 0 20px 0; font-size: 26px; font-weight: 600;">Bonjour ' . htmlspecialchars($prenom) . ' ! 👋</h2>
                            
                            <p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;">
                                Merci de vous être inscrit sur <strong style="color: #f59e0b;">ReVente-Auto</strong>, votre plateforme de vente de véhicules d\'occasion de confiance.
                            </p>
                            
                            <p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 30px 0;">
                                Pour activer votre compte et commencer à publier vos annonces, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous :
                            </p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 10px 0;">
                                        <a href="' . htmlspecialchars($url) . '" style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);">
                                            ✓ Vérifier mon email
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                ⏰ Ce lien est valide pendant <strong style="color: #0f172a;">24 heures</strong>. Après cette période, vous devrez demander un nouveau lien.
                            </p>
                            
                            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0;">
                                Si vous n\'avez pas créé de compte sur ReVente-Auto, vous pouvez ignorer cet email en toute sécurité.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Pied de page -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0;">
                                © ' . date('Y') . ' ReVente-Auto. Tous droits réservés.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Template HTML pour l'email de réinitialisation de mot de passe
     */
    private static function genererTemplateResetMotDePasse($prenom, $url) {
        return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f8fafc;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);">
                    <!-- En-tête -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: 700; letter-spacing: -0.5px;">🚗 ReVente-Auto</h1>
                        </td>
                    </tr>
                    
                    <!-- Contenu -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #0f172a; margin: 0 0 20px 0; font-size: 26px; font-weight: 600;">Réinitialisation du mot de passe</h2>
                            
                            <p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 20px 0;">
                                Bonjour ' . htmlspecialchars($prenom) . ',
                            </p>
                            
                            <p style="color: #475569; font-size: 16px; line-height: 1.7; margin: 0 0 30px 0;">
                                Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe :
                            </p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 10px 0;">
                                        <a href="' . htmlspecialchars($url) . '" style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; text-decoration: none; padding: 16px 48px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 6px rgba(245, 158, 11, 0.3);">
                                            🔑 Réinitialiser mon mot de passe
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                ⏰ Ce lien est valide pendant <strong style="color: #0f172a;">1 heure</strong>.
                            </p>
                            
                            <p style="color: #dc2626; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0; padding: 15px; background-color: #fef2f2; border-left: 4px solid #dc2626; border-radius: 4px;">
                                ⚠️ Si vous n\'avez pas demandé cette réinitialisation, veuillez ignorer cet email et votre mot de passe restera inchangé.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Pied de page -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0;">
                                © ' . date('Y') . ' ReVente-Auto. Tous droits réservés.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
