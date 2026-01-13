<?php
/**
 * Contrôleur de la page À propos
 */
class ControleurApropos extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'À propos - ReVente-Auto';
        $this->pageActive = 'apropos';
    }
    
    public function index(): void {
        $this->rendu('statique/apropos');
    }
}
