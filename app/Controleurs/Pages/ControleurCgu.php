<?php
/**
 * Contrôleur de la page CGU
 */
class ControleurCgu extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Conditions Générales d\'Utilisation - ReVente-Auto';
        $this->pageActive = 'cgu';
    }
    
    public function index(): void {
        $this->rendu('statique/cgu');
    }
}
