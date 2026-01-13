<?php
/**
 * Contrôleur de la page Mes Annonces
 */
class ControleurMesAnnoncesPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Mes Annonces - ReVente-Auto';
        $this->pageActive = 'mes-annonces';
    }
    
    public function index(): void {
        // Exiger que l'utilisateur soit connecté
        $this->exigerConnexion();
        
        $this->rendu('utilisateur/mes_annonces');
    }
}
