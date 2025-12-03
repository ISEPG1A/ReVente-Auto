<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurGalerie {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $filtres = [];
            if (isset($_GET['q'])) {
                $filtres['recherche'] = trim($_GET['q']);
            }
            // Autres filtres possibles (prix, année, etc.)
            
            $vehicules = $this->modele->obtenirTous($filtres);
            Utils::envoyerJSON($vehicules);
        } else {
            Utils::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }
    }
}

$controleur = new ControleurGalerie();
$controleur->traiterRequete();
