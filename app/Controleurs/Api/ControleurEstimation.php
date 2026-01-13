<?php

class ControleurEstimation {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleEstimation();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
        }

        $donnees = Utilitaires::lireCorpsJSON();

        // SÉCURITÉ : Validation stricte des données d'entrée
        $erreurs = [];
        
        // Validation marque
        if (empty($donnees['marque']) || !Utilitaires::chaineValide($donnees['marque'], 50)) {
            $erreurs[] = 'Marque invalide ou manquante.';
        }
        
        // Validation modèle
        if (empty($donnees['modele']) || !Utilitaires::chaineValide($donnees['modele'], 50)) {
            $erreurs[] = 'Modèle invalide ou manquant.';
        }
        
        // Validation année
        if (empty($donnees['annee']) || !Utilitaires::entierEntre($donnees['annee'], 1900, (int)date('Y') + 1)) {
            $erreurs[] = 'Année invalide ou manquante.';
        }
        
        // Validation kilométrage (optionnel mais doit être valide si présent)
        if (isset($donnees['km']) && (!is_numeric($donnees['km']) || (int)$donnees['km'] < 0 || (int)$donnees['km'] > 9999999)) {
            $erreurs[] = 'Kilométrage invalide.';
        }
        
        // Validation carburant (optionnel mais doit être valide si présent)
        if (isset($donnees['carburant']) && !empty($donnees['carburant'])) {
            $carburantsAutorise = ['Essence', 'Diesel', 'GPL', 'Électrique', 'Hybride'];
            if (!in_array($donnees['carburant'], $carburantsAutorise)) {
                $erreurs[] = 'Type de carburant invalide.';
            }
        }
        
        // Validation état (optionnel mais doit être valide si présent)
        if (isset($donnees['etat']) && !empty($donnees['etat'])) {
            $etatsAutorise = ['Excellent', 'Bon', 'Correct', 'À rénover'];
            if (!in_array($donnees['etat'], $etatsAutorise)) {
                $erreurs[] = 'État du véhicule invalide.';
            }
        }
        
        if (!empty($erreurs)) {
            Utilitaires::envoyerJSON(['erreur' => implode(' ', $erreurs)], 400);
        }

        try {
            $resultat = $this->modele->estimer($donnees);
            Utilitaires::envoyerJSON($resultat);
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }
}

// Point d'entrée
$controleur = new ControleurEstimation();
$controleur->traiterRequete();
