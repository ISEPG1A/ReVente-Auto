<?php
/**
 * Contrôleur de la page d'ajout de véhicule
 */
class ControleurAjoutPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Ajouter un véhicule - ReVente-Auto';
        $this->pageActive = 'ajout_vehicule';
    }
    
    public function index(): void {
        // Exiger que l'utilisateur soit connecté
        $this->exigerConnexion();
        
        $this->rendu('vehicule/ajout');
    }
}
