<?php

// Démarrage de la session si nécessaire pour le CSRF
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Contrôleur gérant les requêtes HTTP pour le formulaire de contact.
 * Reçoit la requête, vérifie la sécurité et appelle le modèle.
 */
class ControleurContact {

    /**
     * Point d'entrée principal du contrôleur.
     * Analyse la méthode HTTP et dirige vers la bonne action.
     */
    public function gererRequete() {
        // On n'accepte que les méthodes POST pour l'envoi
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->envoyerMessage();
        } else {
            Utils::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
        }
    }

    /**
     * Gère l'action d'envoi de message.
     * Vérifie le CSRF, le Honeypot et appelle le modèle.
     */
    private function envoyerMessage() {
        // Vérification du jeton CSRF
        $jetonRecu = $_POST['jeton'] ?? '';
        $jetonSession = $_SESSION['contact_csrf'] ?? '';

        if (empty($jetonRecu) || $jetonRecu !== $jetonSession) {
            Utils::envoyerJSON(['erreur' => 'Session expirée ou invalide.'], 403);
        }

        // Vérification du Honeypot (champ piège pour les robots)
        if (!empty($_POST['site_web'])) {
            // On simule un succès pour ne pas alerter le robot
            Utils::envoyerJSON(['succes' => true]);
        }

        try {
            $modele = new ModeleContact();
            // On passe $_POST directement car c'est un formulaire standard (FormData)
            $resultat = $modele->traiterMessage($_POST);
            Utils::envoyerJSON($resultat);
        } catch (Exception $e) {
            Utils::envoyerJSON(['erreur' => $e->getMessage()], 400);
        }
    }
}

// Instanciation et exécution immédiate du contrôleur
$controleur = new ControleurContact();
$controleur->gererRequete();
