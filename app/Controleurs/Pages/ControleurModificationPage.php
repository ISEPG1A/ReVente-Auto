<?php
/**
 * Contrôleur de la page de modification de véhicule
 */
class ControleurModificationPage extends ControleurBase {
    
    public function __construct() {
        parent::__construct();
        $this->titrePage = 'Modifier le véhicule - ReVente-Auto';
        $this->pageActive = 'galerie';
    }
    
    public function index(): void {
        // Exiger que l'utilisateur soit connecté
        $this->exigerConnexion();
        
        // Vérifier l'ID du véhicule
        $vehiculeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($vehiculeId <= 0) {
            $this->rediriger('mes-annonces');
            return;
        }
        
        // Vérifier que le véhicule existe et appartient à l'utilisateur
        $modeleVehicule = new ModeleVehicule();
        $vehicule = $modeleVehicule->obtenirParId($vehiculeId);
        
        if (!$vehicule) {
            $this->rediriger('mes-annonces');
            return;
        }
        
        $utilisateur = $this->obtenirUtilisateur();
        if ($vehicule['user_id'] !== $utilisateur['id']) {
            $this->rediriger('mes-annonces');
            return;
        }
        
        $this->titrePage = 'Modifier ' . $vehicule['marque'] . ' ' . $vehicule['modele'];
        
        $this->rendu('vehicule/modification', [
            'vehicule' => $vehicule,
            'vehiculeId' => $vehiculeId
        ]);
    }
}
