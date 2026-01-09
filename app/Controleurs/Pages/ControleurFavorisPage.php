<?php
/**
 * Contrôleur de la page des favoris
 */
class ControleurFavorisPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Mes Favoris - ReVente-Auto';
        $this->pageActive = 'favoris';
    }
    
    public function index(): void {
        // Exiger que l'utilisateur soit connecté
        $this->exigerConnexion();
        
        $this->rendu('utilisateur/favoris');
    }
}
