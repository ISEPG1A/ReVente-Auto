<?php

class ModeleVehicule {
    private $connexion;

    public function __construct() {
        $this->connexion = BaseDeDonnees::obtenirConnexion();
    }

    public function obtenirTous($filtres = []) {
        $sql = "SELECT v.*,
                       u.first_name as seller_first_name, u.last_name as seller_last_name,
                       u.email as seller_email, u.phone as seller_phone
                FROM vehicles v
                LEFT JOIN users u ON u.id = v.user_id";
        
        $params = [];
        $conditions = [];

        if (!empty($filtres['recherche'])) {
            $conditions[] = "(v.marque LIKE :q OR v.modele LIKE :q)";
            $params[':q'] = "%" . $filtres['recherche'] . "%";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY v.created_at DESC";

        $stmt = $this->connexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenirParId($id) {
        $sql = "SELECT v.*, 
                       u.first_name as seller_first_name, u.last_name as seller_last_name,
                       u.email as seller_email, u.phone as seller_phone, u.avatar_path as seller_avatar
                FROM vehicles v
                LEFT JOIN users u ON u.id = v.user_id
                WHERE v.id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function ajouter($donnees, $fichierImage, $userId) {
        // Gestion de l'image
        $imagePath = null;
        if ($fichierImage && $fichierImage['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $extension = pathinfo($fichierImage['name'], PATHINFO_EXTENSION);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array(strtolower($extension), $allowedExtensions)) {
                $filename = uniqid('v_') . '.' . $extension;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($fichierImage['tmp_name'], $targetPath)) {
                    $imagePath = 'uploads/' . $filename;
                }
            }
        }

        // Insertion
        $sql = "INSERT INTO vehicles (marque, modele, annee, prix, km, carburant, boite, description, ville, image_path, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->connexion->prepare($sql);
            $stmt->execute([
                $donnees['marque'],
                $donnees['modele'],
                (int)$donnees['annee'],
                (float)$donnees['prix'],
                (int)($donnees['km'] ?? 0),
                $donnees['carburant'] ?? '',
                $donnees['boite'] ?? '',
                $donnees['description'] ?? '',
                $donnees['ville'] ?? '',
                $imagePath,
                $userId
            ]);
            
            return $this->obtenirParId($this->connexion->lastInsertId());
        } catch (PDOException $e) {
            // Fallback si colonnes manquantes (compatibilité)
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                $sqlFallback = "INSERT INTO vehicles (marque, modele, annee, prix, user_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->connexion->prepare($sqlFallback);
                $stmt->execute([
                    $donnees['marque'],
                    $donnees['modele'],
                    (int)$donnees['annee'],
                    (float)$donnees['prix'],
                    $userId
                ]);
                return $this->obtenirParId($this->connexion->lastInsertId());
            }
            throw $e;
        }
    }

    public function supprimer($id, $userId, $isAdmin) {
        // Vérifier existence et droits
        $vehicule = $this->obtenirParId($id);
        if (!$vehicule) return false;

        $estProprietaire = (int)$vehicule['user_id'] === (int)$userId;
        
        if (!$estProprietaire && !$isAdmin) {
            throw new Exception("Non autorisé");
        }

        $stmt = $this->connexion->prepare("DELETE FROM vehicles WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
