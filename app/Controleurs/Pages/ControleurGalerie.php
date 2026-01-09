<?php
/**
 * Contrôleur de la page galerie (liste des véhicules)
 */
class ControleurGalerie extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Galerie - ReVente-Auto';
        $this->pageActive = 'galerie';
    }
    
    public function index(): void {
        // La galerie charge les véhicules via JavaScript/API
        // Le contrôleur prépare juste la vue avec les filtres initiaux si présents
        $this->rendu('vehicule/galerie', [
            'filtresInitiaux' => $_GET
        ]);
    }
}
