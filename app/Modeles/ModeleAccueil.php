<?php
/**
 * Modèle pour la page d'accueil
 * Gère les statistiques et données spécifiques à l'accueil
 */
class ModeleAccueil {
    
    /**
     * Récupère les statistiques de véhicules par type
     * @return array Tableau avec les compteurs par type (voiture, moto, camion)
     */
    public function obtenirStatistiquesVehicules(): array {
        $connexion = BaseDeDonnees::obtenirConnexion();
        
        $sql = "SELECT 
                    type_vehicule,
                    COUNT(*) as total
                FROM vehicles 
                WHERE status = 'public'
                GROUP BY type_vehicule";
        
        $stmt = $connexion->prepare($sql);
        $stmt->execute();
        $resultats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'voiture' => 0,
            'moto' => 0,
            'camion' => 0
        ];
        
        foreach ($resultats as $row) {
            $type = $row['type_vehicule'];
            if (isset($stats[$type])) {
                $stats[$type] = (int)$row['total'];
            }
        }
        
        return $stats;
    }
    
    /**
     * Récupère le nombre total de véhicules publics
     * @return int
     */
    public function obtenirNombreTotalVehicules(): int {
        $connexion = BaseDeDonnees::obtenirConnexion();
        
        $sql = "SELECT COUNT(*) as total FROM vehicles WHERE status = 'public'";
        $stmt = $connexion->prepare($sql);
        $stmt->execute();
        
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * Récupère le nombre total d'utilisateurs inscrits
     * @return int
     */
    public function obtenirNombreUtilisateurs(): int {
        $connexion = BaseDeDonnees::obtenirConnexion();
        
        $sql = "SELECT COUNT(*) as total FROM users";
        $stmt = $connexion->prepare($sql);
        $stmt->execute();
        
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
