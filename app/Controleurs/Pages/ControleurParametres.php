<?php
/**
 * Contrôleur de la page des paramètres/profil
 */
class ControleurParametres extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Paramètres - ReVente-Auto';
        $this->pageActive = 'parametres';
    }
    
    public function index(): void {
        // Exiger que l'utilisateur soit connecté
        $this->exigerConnexion();
        
        $utilisateur = $this->obtenirUtilisateur();
        
        $this->rendu('utilisateur/parametres', [
            'utilisateur' => $utilisateur
        ]);
    }
}
