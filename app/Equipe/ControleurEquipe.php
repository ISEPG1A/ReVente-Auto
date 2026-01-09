<?php
require_once __DIR__ . '/ModeleEquipe.php';

class ControleurEquipe {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleEquipe();
    }

    /**
     * Affiche la page équipe avec tous les membres ayant le rôle admin
     * Récupère les données via le modèle et les passe à la vue
     */
    public function afficherEquipeAdmin() {
        // Récupérer les membres admin via le modèle
        $membres = $this->modele->recup_admin();
        
        // Retourner les données et la configuration de la page
        return [
            'membres' => $membres,
            'view' => 'equipe.php',
            'title' => 'Notre équipe',
            'current' => 'equipe'
        ];
    }
}