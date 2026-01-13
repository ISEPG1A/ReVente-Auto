<?php
/**
 * Contrôleur de la page Estimation
 */
class ControleurEstimationPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Estimation Prix - ReVente-Auto';
        $this->pageActive = 'estimation';
    }
    
    public function index(): void {
        $this->rendu('outils/estimation');
    }
}
