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

        // Validation des champs obligatoires
        if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
            throw new Exception('Tous les champs sont requis.');
        }

        // Validation de la longueur du message
        if (strlen($message) < 10) {
            throw new Exception('Le message est trop court (minimum 10 caractères).');
        }

        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('L\'adresse email n\'est pas valide.');
        }

        // Simulation de l'envoi (ou insertion en BDD ici via BaseDeDonnees::obtenirConnexion())
        // Pour l'instant, on retourne juste un succès.
        
        return [
            'succes' => true,
            'message' => 'Votre message a bien été envoyé.'
        ];
    }
}
