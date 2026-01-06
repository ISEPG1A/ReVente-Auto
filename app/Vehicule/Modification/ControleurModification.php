<?php

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * 🔒 CONTRÔLEUR MODIFICATION - VERSION SÉCURISÉE DÉFINITIVE
 * 
 * Utilise ValidateurVehicule pour toutes les validations
 * 100% sécurisé contre : injection SQL, XSS, CSRF, upload malveillant, incohérences métier
 * + Vérification propriétaire/admin
 */
class ControleurModification {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->gererPost($id);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->gererGet($id);
        } else {
            Utilitaires::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }
    }
    
    /**
     * GET : Récupérer données véhicule (pour affichage formulaire)
     */
    private function gererGet($id) {
        // 1️⃣ Authentification
        if (!GestionnaireSession::estConnecte()) {
            Utilitaires::envoyerJSON(['error' => 'Authentification requise'], 401);
        }
        
        $userId = (int)$_SESSION['user']['id'];
        $estAdmin = $_SESSION['user']['est_administrateur'] ?? false;
        
        // 2️⃣ Récupérer véhicule
        $vehicule = $this->modele->obtenirParId($id);
        
        if (!$vehicule) {
            Utilitaires::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
        }
        
        // 3️⃣ Vérifier autorisation (propriétaire ou admin)
        $estProprietaire = ((int)$vehicule['user_id'] === $userId);
        
        if (!$estProprietaire && !$estAdmin) {
            Utilitaires::envoyerJSON(['error' => 'Vous n\'êtes pas autorisé à modifier ce véhicule'], 403);
        }
        
        Utilitaires::envoyerJSON(['vehicule' => $vehicule], 200);
    }
    
    /**
     * POST : Modifier véhicule
     */
    private function gererPost($id) {
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
        $estAdmin = $_SESSION['user']['est_administrateur'] ?? false;
        
        // 3️⃣ Vérifier propriété
        $vehicule = $this->modele->obtenirParId($id);
        
        if (!$vehicule) {
            Utilitaires::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
        }
        
        $estProprietaire = ((int)$vehicule['user_id'] === $userId);
        
        if (!$estProprietaire && !$estAdmin) {
            Utilitaires::envoyerJSON(['error' => 'Vous n\'êtes pas autorisé à modifier ce véhicule'], 403);
        }
        
        // 4️⃣ 🔒 VALIDATION CENTRALISÉE (100% SÉCURISÉ)
        $resultatValidation = ValidateurVehicule::valider($_POST, $_FILES, 'modification');
        
        if (!$resultatValidation['valide']) {
            Utilitaires::envoyerJSON(['error' => implode(' ', $resultatValidation['erreurs'])], 400);
        }
        
        // 5️⃣ Préparer données nettoyées
        $donnees = $resultatValidation['donnees_nettoyees'];
        
        // 6️⃣ Mettre à jour BDD via modèle existant
        try {
            $fichiersImages = $_FILES['images'] ?? null;
            // Décodage du JSON des images existantes
            $imagesExistantes = json_decode($_POST['images_existantes'] ?? '[]', true);
            if (!is_array($imagesExistantes)) {
                $imagesExistantes = [];
            }
            
            $resultatModification = $this->modele->modifier($id, $donnees, $fichiersImages, $imagesExistantes);
            
            // 7️⃣ Recalculer le score IA après modification
            try {
                require_once __DIR__ . '/../../ScoreIA/ModeleScoreIA.php';
                $modeleScoreIA = new ModeleScoreIA();
                $modeleScoreIA->calculerEtSauvegarder($id, $donnees);
            } catch (Exception $e) {
                // Ignorer l'erreur du score IA - ne pas bloquer la modification
                error_log('Erreur recalcul score IA: ' . $e->getMessage());
            }
            
            Utilitaires::envoyerJSON(['ok' => true, 'vehicule' => $resultatModification], 200);
        } catch (Throwable $e) {
            Utilitaires::envoyerJSON(['error' => $e->getMessage()], 500);
        }
    }
}

// 🔒 VALIDATION ID : Cast et vérification plage
$idInput = $_GET['id'] ?? 0;
if (is_array($idInput)) $idInput = 0; // Protection contre injection tableau id[]=...
$id = (int)$idInput;

if ($id <= 0 || $id > 2147483647) {
    Utilitaires::envoyerJSON(['error' => 'ID véhicule invalide'], 400);
}

$controleur = new ControleurModification();
$controleur->traiterRequete($id);
