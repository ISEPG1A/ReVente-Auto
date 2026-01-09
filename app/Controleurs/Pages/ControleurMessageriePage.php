<?php
/**
 * Contrôleur de la page Messagerie
 */
class ControleurMessageriePage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Messagerie Sécurisée - ReVente-Auto';
        $this->pageActive = 'messagerie';
    }
    
    public function index(): void {
        // Exiger que l'utilisateur soit connecté
        $this->exigerConnexion();
        
        $this->rendu('utilisateur/messagerie');
    }
}
