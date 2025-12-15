<?php

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * 🔒 CONTRÔLEUR AJOUT - VERSION SÉCURISÉE DÉFINITIVE
 * 
 * Utilise ValidateurVehicule pour toutes les validations
 * 100% sécurisé contre : injection SQL, XSS, CSRF, upload malveillant, incohérences métier
 */
class ControleurAjout {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->gererPost();
        } else {
            Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }
    }

    private function gererPost() {
        // 1️⃣ Authentification
        if (!GestionnaireSession::estConnecte()) {
            Utilitaires::envoyerJSON(['error' => 'Authentification requise'], 401);
        }
        
        // 2️⃣ Validation CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (is_array($csrfToken)) $csrfToken = ''; // Protection type
        
        if (!GestionnaireSession::validerTokenCSRF($csrfToken)) {
            Utilitaires::envoyerJSON(['error' => 'Token CSRF invalide. Veuillez recharger la page.'], 403);
        }

        $userId = (int)$_SESSION['user']['id'];
        
        // 3️⃣ Rate Limiting
        $rateLimitCheck = GestionnaireSession::verifierLimiteAnnonces($userId);
        if (!$rateLimitCheck['autorise']) {
            Utilitaires::envoyerJSON([
                'error' => $rateLimitCheck['message'],
                'compteur' => $rateLimitCheck['compteur'],
                'limite' => $rateLimitCheck['limite']
            ], 429);
        }

        // 4️⃣ 🔒 VALIDATION CENTRALISÉE (100% SÉCURISÉ)
        $resultatValidation = ValidateurVehicule::valider($_POST, $_FILES, 'ajout');
        
        if (!$resultatValidation['valide']) {
            Utilitaires::envoyerJSON(['error' => implode(' ', $resultatValidation['erreurs'])], 400);
        }
        
        // 5️⃣ Traitement images (upload sécurisé)
        // Le modèle ajouter() gère déjà l'upload et la validation via ValidateurVehicule
        
        // 6️⃣ Préparer données nettoyées pour BDD
        $donnees = $resultatValidation['donnees_nettoyees'];
        $donnees['user_id'] = $userId;
        
        // 7️⃣ Insérer en BDD via modèle existant
        try {
            $fichiersImages = $_FILES['images'] ?? null;
            $nouveauVehicule = $this->modele->ajouter($donnees, $fichiersImages, $userId);
            
            // 8️⃣ Incrémenter compteur annonces (rate limiting)
            GestionnaireSession::incrementerCompteurAnnonces($userId);
            
            Utilitaires::envoyerJSON(['ok' => true, 'vehicle' => $nouveauVehicule], 201);
        } catch (Throwable $e) {
            Utilitaires::envoyerJSON(['error' => $e->getMessage()], 500);
        }
    }
}

$controleur = new ControleurAjout();
$controleur->traiterRequete();
