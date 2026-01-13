<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR DE LA PAGE ÉQUIPE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Affiche la page équipe avec tous les membres ayant le rôle admin
 * Les données sont récupérées dynamiquement depuis la base de données
 */
class ControleurEquipe extends ControleurBase {
    
    /**
     * Instance du modèle équipe
     */
    private ModeleEquipe $modele;
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Notre équipe - ReVente-Auto';
        $this->pageActive = 'equipe';
        $this->modele = new ModeleEquipe();
    }
    
    /**
     * Affiche la page équipe avec les membres admin
     */
    public function index(): void {
        // Récupérer les membres admin via le modèle
        $membres = $this->modele->obtenirAdmins();
        
        $this->rendu('statique/equipe', [
            'membres' => $membres
        ]);
    }
}
