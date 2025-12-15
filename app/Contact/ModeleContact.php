<?php

/**
 * Modèle gérant la logique métier du formulaire de contact.
 * S'occupe de la validation des données et de l'enregistrement/envoi du message.
 */
class ModeleContact {

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

        // SÉCURITÉ : Validation de la longueur du message (10-5000 caractères)
        if (strlen($message) < 10) {
            throw new Exception('Le message est trop court (minimum 10 caractères).');
        }
        
        if (strlen($message) > 5000) {
            throw new Exception('Le message est trop long (maximum 5000 caractères).');
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

        // Simulation de l'envoi (ou insertion en BDD ici via BaseDeDonnees::obtenirConnexion())
        // Pour l'instant, on retourne juste un succès.
        
        return [
            'succes' => true,
            'message' => 'Votre message a bien été envoyé.'
        ];
    }
}
