<?php
/**
 * Contrôleur de la page 404
 */
class ControleurErreur extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Page introuvable - ReVente-Auto';
        $this->pageActive = '';
    }
    
    public function index(): void {
        http_response_code(404);
        $this->rendu('erreur/404');
    }
}
