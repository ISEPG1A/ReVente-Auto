<?php
/**
 * Contrôleur de la page FAQ
 */
class ControleurFaq extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Questions Fréquentes - FAQ - ReVente-Auto';
        $this->pageActive = 'faq';
    }
    
    public function index(): void {
        $this->rendu('statique/faq');
    }
}
