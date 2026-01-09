<?php

/**
 * Modèle gérant la gestion des favoris (ajout, suppression, liste).
 */
class ModeleFavoris {

    /**
     * Récupère la liste des véhicules favoris pour un utilisateur.
     * 
     * @param int $idUtilisateur L'identifiant de l'utilisateur
     * @return array La liste des véhicules favoris avec leurs détails
     */
    public function obtenirFavoris($idUtilisateur) {
        $db = BaseDeDonnees::obtenirConnexion();
        
        $sql = "
            SELECT v.*, 
                   u.first_name as seller_first_name, u.last_name as seller_last_name,
                   u.email as seller_email, u.phone as seller_phone
            FROM favorites f
            JOIN vehicles v ON f.vehicle_id = v.id
            LEFT JOIN users u ON u.id = v.user_id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ";
        
        $requete = $db->prepare($sql);
        $requete->execute([$idUtilisateur]);
        
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère uniquement les IDs des véhicules favoris (pour l'affichage des coeurs).
     * 
     * @param int $idUtilisateur L'identifiant de l'utilisateur
     * @return array Liste des IDs de véhicules
     */
    public function obtenirIdsFavoris($idUtilisateur) {
        $db = BaseDeDonnees::obtenirConnexion();
        
        $requete = $db->prepare("SELECT vehicle_id FROM favorites WHERE user_id = ?");
        $requete->execute([$idUtilisateur]);
        
        return $requete->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Ajoute un véhicule aux favoris.
     * 
     * @param int $idUtilisateur L'identifiant de l'utilisateur
     * @param int $idVehicule L'identifiant du véhicule
     * @return bool Vrai si ajouté, Faux si déjà présent
     */
    public function ajouterFavori($idUtilisateur, $idVehicule) {
        $db = BaseDeDonnees::obtenirConnexion();
        
        // Vérifier si déjà en favori
        $check = $db->prepare("SELECT 1 FROM favorites WHERE user_id = ? AND vehicle_id = ?");
        $check->execute([$idUtilisateur, $idVehicule]);
        
        if (!$check->fetch()) {
            $insert = $db->prepare("INSERT INTO favorites (user_id, vehicle_id) VALUES (?, ?)");
            $insert->execute([$idUtilisateur, $idVehicule]);
            return true;
        }
        
        return false;
    }

    /**
     * Retire un véhicule des favoris.
     * 
     * @param int $idUtilisateur L'identifiant de l'utilisateur
     * @param int $idVehicule L'identifiant du véhicule
     */
    public function supprimerFavori($idUtilisateur, $idVehicule) {
        $db = BaseDeDonnees::obtenirConnexion();
        
        $delete = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND vehicle_id = ?");
        $delete->execute([$idUtilisateur, $idVehicule]);
    }
}
