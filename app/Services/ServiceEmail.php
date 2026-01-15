<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SERVICE EMAIL - ENVOI D'EMAILS VIA SMTP (REFACTORISÉ)
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce service gère l'envoi d'emails via SMTP (Gmail).
 * Les templates HTML sont externalisés dans app/Templates/Email/
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0 - Templates externalisés
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ServiceEmail {
    private static $config = null;
    
    /**
     * 🔒 SÉCURITÉ : Mode débogage SMTP - DÉSACTIVER EN PRODUCTION
     */
    private const DEBUG_SMTP = false;

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
     * Log de débogage conditionnel
     */
    private static function debugLog($message) {
        if (self::DEBUG_SMTP) {
            error_log('[SMTP DEBUG] ' . $message);
        }
    }

    /**
     * Charge et rend un template email
     * 
     * @param string $nomTemplate Nom du fichier template (sans .php)
     * @param array $variables Variables à passer au template
     * @return string HTML généré
     */
    private static function chargerTemplate($nomTemplate, $variables = []) {
        $cheminTemplate = __DIR__ . '/../Templates/Email/' . $nomTemplate . '.php';
        
        if (!file_exists($cheminTemplate)) {
            throw new Exception("Template email introuvable : $nomTemplate");
        }
        
        // Extraire les variables pour qu'elles soient disponibles dans le template
        extract($variables);
        
        // Capturer le rendu du template
        ob_start();
        include $cheminTemplate;
        return ob_get_clean();
    }

    /**
     * Envoie un email via SMTP
     * 
     * @param string $destinataire Email du destinataire
     * @param string $sujet Sujet de l'email
     * @param string $corpsHTML Corps HTML de l'email
     * @param string $corpsTexte Corps texte brut (fallback)
     * @return bool True si envoyé avec succès
     */
    public static function envoyer($destinataire, $sujet, $corpsHTML, $corpsTexte = '') {
        $config = self::chargerConfig();

        self::debugLog('Envoi email à: ' . $destinataire);
        
        if (empty($config['smtp_user']) || empty($config['smtp_pass'])) {
            error_log('ERREUR: Configuration SMTP incomplète');
            throw new Exception('Configuration SMTP incomplète.');
        }

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
     * Obtient l'URL de base de l'application
     */
    private static function obtenirUrlBase() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $basePath = str_replace('\\', '/', dirname($scriptName));
        
        if (strpos($basePath, '/public') !== false) {
            $basePath = substr($basePath, 0, strpos($basePath, '/public'));
        }
        
        $basePath = rtrim($basePath, '/');
        if ($basePath === '.') {
            $basePath = '';
        }
        
        return $protocol . '://' . $host . $basePath;
    }

    // =========================================================================
    // MÉTHODES D'ENVOI AVEC TEMPLATES
    // =========================================================================

    /**
     * Envoie un email de vérification d'adresse
     */
    public static function envoyerVerificationEmail($destinataire, $prenom, $token) {
        $url = self::obtenirUrlBase() . "/api/profil?action=verify-email&token=" . urlencode($token);
        
        $corpsHTML = self::chargerTemplate('verification_email', [
            'prenom' => $prenom,
            'url' => $url
        ]);
        
        $corpsTexte = "Bonjour $prenom,\n\n" .
            "Merci de vous être inscrit sur ReVente-Auto !\n\n" .
            "Pour activer votre compte, veuillez cliquer sur le lien suivant :\n" .
            "$url\n\n" .
            "Ce lien est valide pendant 24 heures.\n\n" .
            "Si vous n'avez pas créé de compte, vous pouvez ignorer cet email.\n\n" .
            "Cordialement,\nL'équipe ReVente-Auto";

        return self::envoyer($destinataire, "Vérifiez votre adresse email - ReVente-Auto", $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie un email de réinitialisation de mot de passe
     */
    public static function envoyerResetMotDePasse($destinataire, $prenom, $token) {
        $url = self::obtenirUrlBase() . "/api/auth/reset-password?action=reset-password&token=" . urlencode($token);

        $corpsHTML = self::chargerTemplate('reset_mot_de_passe', [
            'prenom' => $prenom,
            'url' => $url
        ]);
        
        $corpsTexte = "Bonjour $prenom,\n\n" .
            "Vous avez demandé la réinitialisation de votre mot de passe.\n\n" .
            "Pour créer un nouveau mot de passe, cliquez sur le lien suivant :\n" .
            "$url\n\n" .
            "Ce lien est valide pendant 1 heure.\n\n" .
            "Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.\n\n" .
            "Cordialement,\nL'équipe ReVente-Auto";

        return self::envoyer($destinataire, "Réinitialisation de votre mot de passe - ReVente-Auto", $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie un email de confirmation pour le changement d'adresse email
     */
    public static function envoyerConfirmationChangementEmail($destinataire, $prenom, $token) {
        $url = self::obtenirUrlBase() . "/api/profil?action=confirm-email-change&token=" . urlencode($token);

        $corpsHTML = self::chargerTemplate('changement_email', [
            'prenom' => $prenom,
            'url' => $url
        ]);
        
        $corpsTexte = "Bonjour $prenom,\n\n" .
            "Vous avez demandé à changer votre adresse email sur ReVente-Auto.\n\n" .
            "Pour confirmer cette nouvelle adresse, cliquez sur le lien suivant :\n" .
            "$url\n\n" .
            "Ce lien est valide pendant 24 heures.\n\n" .
            "Si vous n'avez pas demandé ce changement, vous pouvez ignorer cet email.\n\n" .
            "Cordialement,\nL'équipe ReVente-Auto";

        return self::envoyer($destinataire, "Confirmez votre nouvelle adresse email - ReVente-Auto", $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie une notification de nouveau message
     */
    public static function envoyerNotificationNouveauMessage($destinataire, $prenomDestinataire, $prenomExpediteur, $nomExpediteur) {
        $url = self::obtenirUrlBase() . "/messagerie";

        $corpsHTML = self::chargerTemplate('nouveau_message', [
            'prenomDestinataire' => $prenomDestinataire,
            'prenomExpediteur' => $prenomExpediteur,
            'nomExpediteur' => $nomExpediteur,
            'url' => $url
        ]);
        
        $corpsTexte = "Bonjour $prenomDestinataire,\n\n" .
            "Vous avez reçu un nouveau message de $prenomExpediteur $nomExpediteur sur ReVente-Auto.\n\n" .
            "Connectez-vous à votre espace messagerie pour lire et répondre :\n" .
            "$url\n\n" .
            "Cordialement,\nL'équipe ReVente-Auto";

        return self::envoyer($destinataire, "Nouveau message de {$prenomExpediteur} - ReVente-Auto", $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie une notification quand un véhicule favori est supprimé
     */
    public static function envoyerNotificationFavoriSupprime($destinataire, $prenomDestinataire, $marque, $modele) {
        $url = self::obtenirUrlBase() . "/galerie";

        $corpsHTML = self::chargerTemplate('favori_supprime', [
            'prenomDestinataire' => $prenomDestinataire,
            'marque' => $marque,
            'modele' => $modele,
            'url' => $url
        ]);
        
        $corpsTexte = "Bonjour $prenomDestinataire,\n\n" .
            "Le véhicule $marque $modele que vous aviez ajouté à vos favoris n'est plus disponible.\n\n" .
            "Il a peut-être été vendu ou retiré par son propriétaire.\n\n" .
            "Découvrez d'autres véhicules similaires sur notre galerie :\n" .
            "$url\n\n" .
            "Cordialement,\nL'équipe ReVente-Auto";

        return self::envoyer($destinataire, "Un véhicule de vos favoris n'est plus disponible - ReVente-Auto", $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie une réponse à un message de contact
     */
    public static function envoyerReponseContact($destinataire, $nom, $sujetOriginal, $messageOriginal, $reponse) {
        $corpsHTML = self::chargerTemplate('reponse_contact', [
            'nom' => $nom,
            'sujetOriginal' => $sujetOriginal,
            'messageOriginal' => $messageOriginal,
            'reponse' => $reponse
        ]);
        
        $corpsTexte = "Bonjour $nom,\n\n" .
            "Merci de nous avoir contactés. Voici notre réponse à votre message :\n\n" .
            "---\n\n$reponse\n\n---\n\n" .
            "Votre message original :\n\"$messageOriginal\"\n\n" .
            "Cordialement,\nL'équipe ReVente-Auto";

        return self::envoyer($destinataire, "Re: {$sujetOriginal} - ReVente-Auto", $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie une notification de modération d'annonce
     */
    public static function envoyerNotificationModeration($destinataire, $prenom, $marque, $modele, $approuvee, $raisonRefus = null) {
        $corpsHTML = self::chargerTemplate('moderation', [
            'prenom' => $prenom,
            'marque' => $marque,
            'modele' => $modele,
            'approuvee' => $approuvee,
            'raisonRefus' => $raisonRefus
        ]);
        
        $sujet = $approuvee 
            ? "✅ Votre annonce {$marque} {$modele} est en ligne ! - ReVente-Auto"
            : "❌ Votre annonce {$marque} {$modele} n'a pas été approuvée - ReVente-Auto";
        
        $corpsTexte = "Bonjour $prenom,\n\n";
        if ($approuvee) {
            $corpsTexte .= "Bonne nouvelle ! Votre annonce pour $marque $modele a été approuvée.\n\n";
        } else {
            $corpsTexte .= "Votre annonce pour $marque $modele n'a pas été approuvée.\n\n";
            if ($raisonRefus) {
                $corpsTexte .= "Raison : $raisonRefus\n\n";
            }
        }
        $corpsTexte .= "Cordialement,\nL'équipe ReVente-Auto";

        return self::envoyer($destinataire, $sujet, $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie une notification de bannissement
     */
    public static function envoyerNotificationBannissement($destinataire, $prenom, $raison) {
        $corpsHTML = self::chargerTemplate('bannissement', [
            'prenom' => $prenom,
            'raison' => $raison
        ]);
        
        $corpsTexte = "Bonjour $prenom,\n\n" .
            "Nous vous informons que votre compte ReVente-Auto a été suspendu.\n\n" .
            "Raison de la suspension :\n$raison\n\n" .
            "Vous ne pourrez plus accéder à votre compte tant que cette suspension sera active.\n\n" .
            "Si vous souhaitez contester cette décision, vous pouvez répondre à cet email.\n\n" .
            "Cordialement,\nL'équipe ReVente-Auto";

        return self::envoyer($destinataire, "Suspension de votre compte - ReVente-Auto", $corpsHTML, $corpsTexte);
    }

    /**
     * Envoie une notification de proposition de prix
     * 
     * @param string $destinataire Email du vendeur
     * @param array $vendeur Infos vendeur ['nom', 'prenom']
     * @param array $acheteur Infos acheteur ['nom', 'prenom']
     * @param array $vehicule Infos véhicule ['titre', 'prix', 'id']
     * @param float $prixPropose Prix proposé par l'acheteur
     * @param int $idConversation ID de la conversation pour le lien messagerie
     * @return bool True si envoyé avec succès
     */
    public static function envoyerPropositionPrix($destinataire, $vendeur, $acheteur, $vehicule, $prixPropose, $idConversation) {
        // Construire les URLs en interne comme les autres méthodes
        $baseUrl = self::obtenirUrlBase();
        $lienVehicule = $baseUrl . '/vehicule/' . ($vehicule['id'] ?? '');
        $lienMessagerie = $baseUrl . '/messagerie?conversation=' . $idConversation;
        
        $corpsHTML = self::chargerTemplate('proposition_prix', [
            'nomVendeur' => $vendeur['nom'],
            'prenomVendeur' => $vendeur['prenom'],
            'nomAcheteur' => $acheteur['nom'],
            'prenomAcheteur' => $acheteur['prenom'],
            'titreVehicule' => $vehicule['titre'],
            'prixAnnonce' => $vehicule['prix'],
            'prixPropose' => $prixPropose,
            'messageAcheteur' => null,
            'lienVehicule' => $lienVehicule,
            'lienMessagerie' => $lienMessagerie
        ]);
        
        $prixFormate = number_format($prixPropose, 0, ',', ' ');
        $corpsTexte = "Bonjour {$vendeur['prenom']},\n\n" .
            "Bonne nouvelle ! {$acheteur['prenom']} {$acheteur['nom']} vous a fait une proposition de prix.\n\n" .
            "Véhicule concerné : {$vehicule['titre']}\n" .
            "Prix proposé : {$prixFormate} €\n\n" .
            "Consultez votre messagerie pour répondre : {$lienMessagerie}\n\n" .
            "Cordialement,\nL'équipe ReVente-Auto";

        $sujet = "Nouvelle proposition de prix pour " . $vehicule['titre'];
        return self::envoyer($destinataire, $sujet, $corpsHTML, $corpsTexte);
    }

    // =========================================================================
    // ENVOI VIA SMTP
    // =========================================================================

    /**
     * Envoi via SMTP natif (sans PHPMailer)
     */
    private static function envoyerViaSMTP($host, $port, $username, $password, $fromEmail, $fromName, $to, $subject, $htmlBody, $textBody) {
        self::debugLog('Connexion à ' . $host . ':' . $port);
        
        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            error_log('ERREUR SMTP: Connexion échouée - ' . $errstr);
            throw new Exception("Impossible de se connecter au serveur SMTP");
        }
        
        self::debugLog('Connexion socket réussie');

        $lireReponse = function() use ($socket) {
            return fgets($socket, 512);
        };

        $envoyerCommande = function($commande) use ($socket) {
            fwrite($socket, $commande . "\r\n");
        };

        try {
            // Lire le message de bienvenue
            $lireReponse();
            self::debugLog("Bienvenue SMTP reçu");

            // EHLO
            $envoyerCommande("EHLO " . $host);
            do {
                $reponse = $lireReponse();
            } while (strpos($reponse, '-') === 3);

            // STARTTLS
            $envoyerCommande("STARTTLS");
            $reponse = $lireReponse();
            
            if (strpos($reponse, '220') !== 0) {
                throw new Exception("Le serveur n'est pas prêt pour TLS");
            }
            
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            stream_set_blocking($socket, true);
            
            if (!stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                throw new Exception("Échec de l'activation TLS");
            }
            
            self::debugLog("TLS activé avec succès");

            // EHLO après TLS
            $envoyerCommande("EHLO " . $host);
            do {
                $reponse = $lireReponse();
            } while (strpos($reponse, '-') === 3);

            // AUTH LOGIN
            $envoyerCommande("AUTH LOGIN");
            $lireReponse();

            $envoyerCommande(base64_encode($username));
            $lireReponse();

            $envoyerCommande(base64_encode($password));
            $reponseAuth = $lireReponse();
            
            if (strpos($reponseAuth, '235') === false) {
                throw new Exception("Échec de l'authentification SMTP");
            }
            
            self::debugLog("Authentification réussie");

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
}
