<?php
/**
 * Contrôleur de la page Politique de Confidentialité
 */
class ControleurPolitiqueConfidentialite extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Politique de Confidentialité - ReVente-Auto';
        $this->pageActive = 'politique-confidentialite';
    }
    
    public function index(): void {
        $this->rendu('statique/politique-confidentialite');
    }
}
