<?php
require_once __DIR__ . '/../Commun/BaseDeDonnees.php';

class ModeleEquipe {
    private $bdd;

    public function __construct() {
        $this->bdd = BaseDeDonnees::obtenirConnexion();
    }

    // Récupère tous les utilisateurs admin de la base de données
    public function recup_admin() {
        $sql = 'SELECT id, last_name, first_name, poste, avatar_path FROM users WHERE role = "admin" ORDER BY id';
        $stmt = $this->bdd->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}