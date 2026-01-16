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
        
        // Récupérer les données de l'utilisateur connecté
        $utilisateurConnecte = $_SESSION['user'] ?? null;
        $estAdmin = isset($utilisateurConnecte['role']) && $utilisateurConnecte['role'] === 'admin';
        
        // Préparer la configuration JavaScript
        $equipeConfig = [
            'baseUrl' => $this->cheminBase,
            'userId' => isset($utilisateurConnecte['id']) ? (int)$utilisateurConnecte['id'] : 0,
            'isAdmin' => $estAdmin ? 'true' : 'false'
        ];
        
        $this->rendu('statique/equipe', [
            'membres' => $membres,
            'utilisateurConnecte' => $utilisateurConnecte,
            'estAdmin' => $estAdmin,
            'equipeConfig' => $equipeConfig
        ]);
    }


}
