<?php
/**
 * Contrôleur de la page Contact
 */
class ControleurContactPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Contact - ReVente-Auto';
        $this->pageActive = 'contact';
    }
    
    public function index(): void {
        $this->rendu('outils/contact');
    }
}
