<?php
require_once __DIR__ . '/ModeleEquipe.php';

class ControleurEquipe {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleEquipe();
    }

    public function afficherEquipeAdmin() {
        $membres = $this->modele->recup_admin();
        include __DIR__ . '/../../views/pages/equipe.php';
    }
}