<?php
/**
 * Contrôleur de la page d'accueil
 */
class ControleurAccueil extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Accueil - ReVente-Auto';
        $this->pageActive = 'accueil';
    }
    
    public function index(): void {
        $modeleVehicule = new ModeleVehicule();
        $modeleAccueil = new ModeleAccueil();
        
        // Récupérer les statistiques par type de véhicule
        $statsVehicules = $modeleAccueil->obtenirStatistiquesVehicules();
        
        // Récupérer le nombre d'utilisateurs
        $nombreUtilisateurs = $modeleAccueil->obtenirNombreUtilisateurs();
        
        // Récupérer les véhicules les plus pertinents pour le carrousel
        // (Score IA élevé, récents, publics)
        $vehiculesCarrousel = $modeleVehicule->obtenirTous([
            'status' => 'public',
            'limit' => 10,
            'tri' => 'score_ia',
            'ordre' => 'DESC'
        ]);
        
        $this->rendu('accueil', [
            'vehicules' => $vehiculesCarrousel,
            'statsVehicules' => $statsVehicules,
            'nombreUtilisateurs' => $nombreUtilisateurs
        ]);
    }
}
