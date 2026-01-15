<?php

/**
 * 🔒 CONTRÔLEUR AJOUT - VERSION SÉCURISÉE DÉFINITIVE
 * 
 * Utilise ValidateurVehicule pour toutes les validations
 * 100% sécurisé contre : injection SQL, XSS, CSRF, upload malveillant, incohérences métier
 */
class ControleurAjoutVehicule {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->gererPost();
        } else {
            Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
        }
    }

    private function gererPost() {
        // 1️⃣ Authentification
        if (!GestionnaireSession::estConnecte()) {
            Utilitaires::envoyerJSON(['erreur' => 'Authentification requise'], 401);
        }
        
        // 1️⃣bis Vérification email vérifié
        if (empty($_SESSION['user']['email_verified_at'])) {
            Utilitaires::envoyerJSON([
                'erreur' => 'Veuillez vérifier votre adresse email avant de publier une annonce.',
                'code' => 'EMAIL_NON_VERIFIE'
            ], 403);
        }
        
        // 2️⃣ Validation CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (is_array($csrfToken)) $csrfToken = ''; // Protection type
        
        if (!GestionnaireSession::validerTokenCSRF($csrfToken)) {
            Utilitaires::envoyerJSON(['erreur' => 'Token CSRF invalide. Veuillez recharger la page.'], 403);
        }

        $userId = (int)$_SESSION['user']['id'];
        
        // 3️⃣ Rate Limiting - Protection contre le spam d'annonces
        if (!GestionnaireLimiteTaux::verifierTentative('vehicle_creation')) {
            $tempsRestant = GestionnaireLimiteTaux::obtenirTempsRestant('vehicle_creation');
            Utilitaires::envoyerJSON([
                'erreur' => "Vous avez atteint la limite de création d'annonces. Veuillez attendre {$tempsRestant} secondes."
            ], 429);
        }
        
        // 4️⃣ Rate Limiting - Limite par utilisateur (10 annonces / heure)
        $rateLimitCheck = GestionnaireSession::verifierLimiteAnnonces($userId);
        if (!$rateLimitCheck['autorise']) {
            Utilitaires::envoyerJSON([
                'erreur' => $rateLimitCheck['message'],
                'compteur' => $rateLimitCheck['compteur'],
                'limite' => $rateLimitCheck['limite']
            ], 429);
        }

        // 4️⃣ 🔒 VALIDATION CENTRALISÉE (100% SÉCURISÉ)
        $resultatValidation = ValidateurVehicule::valider($_POST, $_FILES, 'ajout');
        
        if (!$resultatValidation['valide']) {
            Utilitaires::envoyerJSON(['erreur' => implode(' ', $resultatValidation['erreurs'])], 400);
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
            
            // Incrémenter le compteur rate limit
            GestionnaireLimiteTaux::ajouterTentative('vehicle_creation');
            
            // Logger l'ajout d'annonce dans admin_logs
            try {
                $modeleAdmin = new ModeleAdmin();
                $modeleAdmin->ajouterLog(
                    'annonce_creation',
                    'Nouvelle annonce créée',
                    [
                        'marque' => $donnees['marque'] ?? '',
                        'modele' => $donnees['modele'] ?? '',
                        'prix' => $donnees['prix'] ?? 0,
                        'annee' => $donnees['annee'] ?? '',
                        'prenom' => $_SESSION['user']['first_name'] ?? '',
                        'nom' => $_SESSION['user']['last_name'] ?? ''
                    ],
                    $userId,
                    $nouveauVehicule['id'],
                    null
                );
            } catch (Exception $logError) {
                error_log('Erreur log ajout annonce: ' . $logError->getMessage());
            }
            
            // 8️⃣ Calculer et sauvegarder le score IA
            try {
                require_once __DIR__ . '/../../Modeles/ModeleScoreIA.php';
                $modeleScoreIA = new ModeleScoreIA();
                $modeleScoreIA->calculerEtSauvegarder($nouveauVehicule['id'], $donnees);
            } catch (Exception $e) {
                // Ignorer l'erreur du score IA - ne pas bloquer l'ajout
                error_log('Erreur calcul score IA: ' . $e->getMessage());
            }
            
            // 9️⃣ Incrémenter compteur annonces (rate limiting)
            GestionnaireSession::incrementerCompteurAnnonces($userId);
            
            Utilitaires::envoyerJSON(['ok' => true, 'vehicle' => $nouveauVehicule], 201);
        } catch (Throwable $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }
}

$controleur = new ControleurAjoutVehicule();
$controleur->traiterRequete();
