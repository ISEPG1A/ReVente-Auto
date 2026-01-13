<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurGalerieVehicule {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $filtres = [];
            
            // SÉCURITÉ : Validation et sanitization de tous les paramètres de recherche
            if (isset($_GET['q'])) {
                $recherche = trim($_GET['q']);
                // Limiter la longueur de la recherche
                if (strlen($recherche) > 100) {
                    Utilitaires::envoyerJSON(['erreur' => 'Terme de recherche trop long (max 100 caractères)'], 400);
                    return;
                }
                $filtres['recherche'] = $recherche;
            }
            
            // Validation du type de véhicule
            if (isset($_GET['type']) && !empty($_GET['type']) && $_GET['type'] !== 'tous') {
                $typesAutorise = ['moto', 'voiture', 'camion', 'Berline', 'SUV', 'Citadine', 'Utilitaire'];
                if (!in_array($_GET['type'], $typesAutorise)) {
                    Utilitaires::envoyerJSON(['erreur' => 'Type de véhicule invalide'], 400);
                    return;
                }
                $filtres['type'] = $_GET['type'];
            }
            
            // Validation du carburant
            if (isset($_GET['carburant']) && !empty($_GET['carburant'])) {
                $carburantsAutorise = ['Essence', 'Diesel', 'GPL', 'Électrique', 'Hybride'];
                $carburants = is_array($_GET['carburant']) ? $_GET['carburant'] : [$_GET['carburant']];
                foreach ($carburants as $carburant) {
                    if (!in_array($carburant, $carburantsAutorise)) {
                        Utilitaires::envoyerJSON(['erreur' => 'Carburant invalide'], 400);
                        return;
                    }
                }
                $filtres['carburant'] = $carburants;
            }
            
            // Validation de la marque
            if (isset($_GET['marque']) && !empty($_GET['marque']) && $_GET['marque'] !== 'toutes') {
                if (!Utilitaires::chaineValide($_GET['marque'], 50)) {
                    Utilitaires::envoyerJSON(['erreur' => 'Marque invalide'], 400);
                    return;
                }
                $filtres['marque'] = $_GET['marque'];
            }
            
            // Validation des prix (min/max)
            if (isset($_GET['prix_min'])) {
                $prixMin = filter_var($_GET['prix_min'], FILTER_VALIDATE_INT);
                if ($prixMin === false || $prixMin < 0 || $prixMin > 999999999) {
                    Utilitaires::envoyerJSON(['erreur' => 'Prix minimum invalide'], 400);
                    return;
                }
                $filtres['prix_min'] = $prixMin;
            }
            
            if (isset($_GET['prix_max'])) {
                $prixMax = filter_var($_GET['prix_max'], FILTER_VALIDATE_INT);
                if ($prixMax === false || $prixMax < 0 || $prixMax > 999999999) {
                    Utilitaires::envoyerJSON(['erreur' => 'Prix maximum invalide'], 400);
                    return;
                }
                $filtres['prix_max'] = $prixMax;
            }
            
            // Validation des années (min/max)
            if (isset($_GET['annee_min'])) {
                if (!Utilitaires::entierEntre($_GET['annee_min'], 1900, (int)date('Y') + 1)) {
                    Utilitaires::envoyerJSON(['erreur' => 'Année minimum invalide'], 400);
                    return;
                }
                $filtres['annee_min'] = (int)$_GET['annee_min'];
            }
            
            if (isset($_GET['annee_max'])) {
                if (!Utilitaires::entierEntre($_GET['annee_max'], 1900, (int)date('Y') + 1)) {
                    Utilitaires::envoyerJSON(['erreur' => 'Année maximum invalide'], 400);
                    return;
                }
                $filtres['annee_max'] = (int)$_GET['annee_max'];
            }
            
            // Validation de la boîte de vitesse
            if (isset($_GET['boite']) && !empty($_GET['boite'])) {
                $boitesAutorise = ['Manuelle', 'Automatique'];
                $boites = is_array($_GET['boite']) ? $_GET['boite'] : [$_GET['boite']];
                foreach ($boites as $boite) {
                    if (!in_array($boite, $boitesAutorise)) {
                        Utilitaires::envoyerJSON(['erreur' => 'Type de boîte invalide'], 400);
                        return;
                    }
                }
                $filtres['boite'] = $boites;
            }
            
            // Validation de l'état
            if (isset($_GET['etat']) && !empty($_GET['etat'])) {
                $etatsAutorise = ['neuf', 'bon', 'moyen', 'mauvais'];
                $etats = is_array($_GET['etat']) ? $_GET['etat'] : [$_GET['etat']];
                foreach ($etats as $etat) {
                    if (!in_array($etat, $etatsAutorise)) {
                        Utilitaires::envoyerJSON(['erreur' => 'État invalide'], 400);
                        return;
                    }
                }
                $filtres['etat'] = $etats;
            }
            
            // Validation Crit'Air
            if (isset($_GET['crit_air']) && !empty($_GET['crit_air'])) {
                $critAirAutorise = ['0', '1', '2', '3', '4', '5'];
                $critAirs = is_array($_GET['crit_air']) ? $_GET['crit_air'] : [$_GET['crit_air']];
                foreach ($critAirs as $critAir) {
                    if (!in_array($critAir, $critAirAutorise)) {
                        Utilitaires::envoyerJSON(['erreur' => 'Crit\'Air invalide'], 400);
                        return;
                    }
                }
                $filtres['crit_air'] = $critAirs;
            }
            
            // Validation nombre de portes
            if (isset($_GET['nb_portes']) && !empty($_GET['nb_portes'])) {
                $portesAutorise = ['2', '3', '4', '5'];
                $portes = is_array($_GET['nb_portes']) ? $_GET['nb_portes'] : [$_GET['nb_portes']];
                foreach ($portes as $porte) {
                    if (!in_array($porte, $portesAutorise)) {
                        Utilitaires::envoyerJSON(['erreur' => 'Nombre de portes invalide'], 400);
                        return;
                    }
                }
                $filtres['nb_portes'] = $portes;
            }
            
            // Validation contrôle technique
            if (isset($_GET['controle_technique']) && !empty($_GET['controle_technique'])) {
                $ctAutorise = ['oui', 'non', 'non_requis'];
                $cts = is_array($_GET['controle_technique']) ? $_GET['controle_technique'] : [$_GET['controle_technique']];
                foreach ($cts as $ct) {
                    if (!in_array($ct, $ctAutorise)) {
                        Utilitaires::envoyerJSON(['erreur' => 'Contrôle technique invalide'], 400);
                        return;
                    }
                }
                $filtres['controle_technique'] = $cts;
            }
            
            $vehicules = $this->modele->obtenirTous($filtres);
            Utilitaires::envoyerJSON($vehicules);
        } else {
            Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
        }
    }
}

$controleur = new ControleurGalerieVehicule();
$controleur->traiterRequete();
