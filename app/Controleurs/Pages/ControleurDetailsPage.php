<?php
/**
 * Contrôleur de la page détails d'un véhicule
 */
class ControleurDetailsPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Détails du véhicule - ReVente-Auto';
        $this->pageActive = 'galerie';
    }
    
    public function index(): void {
        // L'ID du véhicule est passé en paramètre GET
        $vehiculeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($vehiculeId <= 0) {
            $this->afficher404();
            return;
        }
        
        // Récupérer le véhicule
        $modeleVehicule = new ModeleVehicule();
        $vehicule = $modeleVehicule->obtenirParId($vehiculeId);
        
        if (!$vehicule) {
            $this->afficher404();
            return;
        }
        
        // Incrémenter le compteur de vues de manière intelligente
        // - Ne compte pas les vues du propriétaire
        // - Ne compte qu'une fois par session
        // - Cooldown de 24h via cookie
        $userId = null;
        if (GestionnaireSession::estConnecte()) {
            $user = GestionnaireSession::obtenirUtilisateur();
            $userId = (int)$user['id'];
        }
        
        if (GestionnaireVues::doitCompterVue($vehiculeId, $userId, $vehicule['user_id'])) {
            $modeleVehicule->incrementerVues($vehiculeId);
            GestionnaireVues::marquerCommeVu($vehiculeId);
        }
        
        // Mettre à jour le titre avec le nom du véhicule
        $this->titrePage = $vehicule['marque'] . ' ' . $vehicule['modele'] . ' - ReVente-Auto';
        
        $this->rendu('vehicule/details', [
            'vehicule' => $vehicule,
            'vehiculeId' => $vehiculeId
        ]);
    }
    
    private function afficher404(): void {
        http_response_code(404);
        $this->titrePage = 'Véhicule introuvable';
        $this->rendu('erreur/404');
    }
}
