<?php

/**
 * Modèle gérant la logique métier du formulaire de contact.
 * S'occupe de la validation des données et de l'enregistrement du message.
 */
class ModeleContact {
    
    private $db;
    
    public function __construct() {
        $this->db = BaseDeDonnees::obtenirConnexion();
    }

    /**
     * Traite l'envoi d'un message de contact.
     * Vérifie les contraintes métier (longueur, champs requis).
     * 
     * @param array $donnees Les données du formulaire (nom, email, sujet, message)
     * @return array Un tableau de succès ou lance une exception
     * @throws Exception Si une validation échoue
     */
    public function traiterMessage($donnees) {
        $nom = trim($donnees['nom'] ?? '');
        $email = trim($donnees['email'] ?? '');
        $sujet = trim($donnees['sujet'] ?? '');
        $message = trim($donnees['message'] ?? '');

        // SÉCURITÉ : Validation des champs obligatoires
        if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
            throw new Exception('Tous les champs sont requis.');
        }

        // SÉCURITÉ : Validation de la longueur du nom (2-100 caractères)
        if (strlen($nom) < 2 || strlen($nom) > 100) {
            throw new Exception('Le nom doit contenir entre 2 et 100 caractères.');
        }

        // SÉCURITÉ : Validation de la longueur du sujet (5-200 caractères)
        if (strlen($sujet) < 5 || strlen($sujet) > 200) {
            throw new Exception('Le sujet doit contenir entre 5 et 200 caractères.');
        }

        // SÉCURITÉ : Validation de la longueur du message (10-1000 caractères)
        if (strlen($message) < 10) {
            throw new Exception('Le message est trop court (minimum 10 caractères).');
        }
        
        if (strlen($message) > 1000) {
            throw new Exception('Le message est trop long (maximum 1000 caractères).');
        }

        // SÉCURITÉ : Validation stricte de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('L\'adresse email n\'est pas valide.');
        }
        
        // Vérifier que l'email n'est pas trop long
        if (strlen($email) > 255) {
            throw new Exception('L\'adresse email est trop longue.');
        }
        
        // SÉCURITÉ : Vérifier les caractères autorisés dans le nom (lettres, espaces, tirets, apostrophes)
        if (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/u", $nom)) {
            throw new Exception('Le nom contient des caractères non autorisés.');
        }

        // Récupérer l'ID utilisateur s'il est connecté
        $userId = $_SESSION['user']['id'] ?? null;
        
        // Enregistrer le message dans la base de données
        $stmt = $this->db->prepare("
            INSERT INTO contacts (nom, email, sujet, message, user_id, ip_address)
            VALUES (:nom, :email, :sujet, :message, :user_id, :ip_address)
        ");
        
        $success = $stmt->execute([
            ':nom' => $nom,
            ':email' => $email,
            ':sujet' => $sujet,
            ':message' => $message,
            ':user_id' => $userId,
            ':ip_address' => Utilitaires::obtenirIpClient()
        ]);
        
        if (!$success) {
            throw new Exception('Erreur lors de l\'enregistrement du message.');
        }
        
        return [
            'succes' => true,
            'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.'
        ];
    }
}
