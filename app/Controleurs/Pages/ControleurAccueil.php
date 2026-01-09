<?php
/**
 * Contrôleur de la page d'accueil
 */
class ControleurAccueil extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Accueil - ReVente-Auto';
        $this->pageActive = 'accueil';
    }
    
    public function index(): void {
        // Récupérer les derniers véhicules pour la page d'accueil
        $modeleVehicule = new ModeleVehicule();
        $derniersVehicules = $modeleVehicule->obtenirTous(['limit' => 6, 'status' => 'public']);
        
        $this->rendu('accueil', [
            'vehicules' => $derniersVehicules
        ]);
    }
}
