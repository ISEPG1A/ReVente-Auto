<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR PAGE ADMIN - Dashboard Administration
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Contrôleur de page pour afficher le dashboard administrateur.
 * Accessible uniquement aux utilisateurs avec le rôle 'admin'.
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ControleurAdminPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Dashboard Admin - ReVente-Auto';
        $this->pageActive = 'admin';
    }
    
    /**
     * Affiche la page du dashboard admin
     */
    public function index(): void {
        // Vérifier que l'utilisateur est connecté
        if (!$this->estConnecte()) {
            http_response_code(404);
            $this->rendu('erreur/404');
            return;
        }
        
        // Vérifier que l'utilisateur est administrateur
        $utilisateur = $this->obtenirUtilisateur();
        if (!isset($utilisateur['role']) || $utilisateur['role'] !== 'admin') {
            http_response_code(404);
            $this->rendu('erreur/404');
            return;
        }
        
        // Afficher le layout avec la vue dashboard
        $this->rendu('admin/dashboard', [
            'utilisateur' => $utilisateur
        ]);
    }
}
