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
        $vehicule = $stmt->fetch();

        if ($vehicule) {
            // Récupérer les images additionnelles
            $stmtImages = $this->connexion->prepare("SELECT image_path FROM vehicle_images WHERE vehicle_id = ? ORDER BY id ASC");
            $stmtImages->execute([$id]);
            $vehicule['images'] = $stmtImages->fetchAll(PDO::FETCH_COLUMN);
            
            // Ajouter l'image principale si elle n'est pas dans la liste (rétrocompatibilité)
            if ($vehicule['image_path'] && !in_array($vehicule['image_path'], $vehicule['images'])) {
                array_unshift($vehicule['images'], $vehicule['image_path']);
            }
        }

        return $vehicule;
    }

    public function ajouter($donnees, $fichiersImages, $userId) {
        // 0. Vérifier le Rate Limit pour l'upload
        if (!GestionnaireLimiteTaux::verifierTentative('upload')) {
            throw new Exception("Limite d'upload atteinte. Veuillez patienter.");
        }
        GestionnaireLimiteTaux::ajouterTentative('upload');

        // 1. Préparation et Validation des images (sans déplacement)
        $imagesATraiter = [];

        // Normaliser le tableau $_FILES
        if (isset($fichiersImages['name']) && is_array($fichiersImages['name'])) {
            $count = count($fichiersImages['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($fichiersImages['error'][$i] === UPLOAD_ERR_OK) {
                    $imagesATraiter[] = [
                        'name' => $fichiersImages['name'][$i],
                        'type' => $fichiersImages['type'][$i],
                        'tmp_name' => $fichiersImages['tmp_name'][$i],
                        'error' => $fichiersImages['error'][$i],
                        'size' => $fichiersImages['size'][$i]
                    ];
                }
            }
        } elseif (isset($fichiersImages['tmp_name']) && !is_array($fichiersImages['tmp_name'])) {
            if ($fichiersImages['error'] === UPLOAD_ERR_OK) {
                $imagesATraiter[] = $fichiersImages;
            }
        }

        // Valider chaque image avant de commencer la transaction
        foreach ($imagesATraiter as $img) {
            $res = ServiceValidationFichier::validerFichier($img);
            if (!$res['valide']) {
                throw new Exception("Erreur image : " . $res['erreur']);
            }
        }

        // 2. Insertion du véhicule (Initialement sans image)
        $sql = "INSERT INTO vehicles (marque, modele, annee, prix, km, carburant, boite, description, ville, image_path, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)";
        
        $this->connexion->beginTransaction();
        
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
                $userId
            ]);
            
            $vehicleId = $this->connexion->lastInsertId();

            // 3. Déplacement des images dans le dossier du véhicule
            $cheminsFinaux = [];
            foreach ($imagesATraiter as $img) {
                $res = ServiceValidationFichier::deplacerImageVehicule($img, $vehicleId);
                if ($res['valide']) {
                    $cheminsFinaux[] = $res['chemin'];
                }
            }

            // 4. Mise à jour des chemins en base
            if (!empty($cheminsFinaux)) {
                // Mettre à jour l'image principale (la première)
                $stmtUpdate = $this->connexion->prepare("UPDATE vehicles SET image_path = ? WHERE id = ?");
                $stmtUpdate->execute([$cheminsFinaux[0], $vehicleId]);

                // Insérer toutes les images dans la table vehicle_images
                $sqlImg = "INSERT INTO vehicle_images (vehicle_id, image_path) VALUES (?, ?)";
                $stmtImg = $this->connexion->prepare($sqlImg);
                foreach ($cheminsFinaux as $chemin) {
                    $stmtImg->execute([$vehicleId, $chemin]);
                }
            }

            $this->connexion->commit();
            return $this->obtenirParId($vehicleId);

        } catch (Throwable $e) {
            $this->connexion->rollBack();
            // Idéalement, on devrait aussi nettoyer les fichiers créés si l'insertion échoue après le déplacement
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

        // Suppression du dossier physique des photos
        ServiceValidationFichier::supprimerDossierVehicule($id);

        // Suppression en base
        $stmt = $this->connexion->prepare("DELETE FROM vehicles WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
