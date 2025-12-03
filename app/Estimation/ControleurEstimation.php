<?php

class ControleurEstimation {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleEstimation();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Utils::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }

        $donnees = Utils::lireCorpsJSON();

        if (empty($donnees['marque']) || empty($donnees['modele']) || empty($donnees['annee'])) {
            Utils::envoyerJSON(['error' => 'Données incomplètes'], 400);
        }

        try {
            $resultat = $this->modele->estimer($donnees);
            Utils::envoyerJSON($resultat);
        } catch (Exception $e) {
            Utils::envoyerJSON(['error' => $e->getMessage()], 500);
        }
    }
}

// Point d'entrée
$controleur = new ControleurEstimation();
$controleur->traiterRequete();
