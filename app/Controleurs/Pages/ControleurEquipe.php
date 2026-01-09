<?php
/**
 * Contrôleur de la page Équipe
 */
class ControleurEquipe extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Notre équipe - ReVente-Auto';
        $this->pageActive = 'equipe';
    }
    
    public function index(): void {
        $this->rendu('statique/equipe');
    }
}
