<?php
/**
 * Contrôleur de la page de connexion/inscription
 */
class ControleurConnexionPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Connexion - ReVente-Auto';
        $this->pageActive = 'connexion';
    }
    
    public function index(): void {
        // Rediriger si déjà connecté
        if ($this->estConnecte()) {
            $this->rediriger('accueil');
            return;
        }
        
        $this->rendu('auth/connexion');
    }
}
