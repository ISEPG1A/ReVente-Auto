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
            $imagesDb = $stmtImages->fetchAll(PDO::FETCH_COLUMN);
            
            // L'image principale (image_path) doit toujours être en premier
            $imagePrincipale = $vehicule['image_path'];
            
            if ($imagePrincipale) {
                // Retirer l'image principale de la liste si elle y est
                $imagesDb = array_filter($imagesDb, fn($img) => $img !== $imagePrincipale);
                // La mettre en premier
                $vehicule['images'] = array_merge([$imagePrincipale], array_values($imagesDb));
            } else {
                $vehicule['images'] = $imagesDb;
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

    /**
     * Modifier un véhicule existant
     * 
     * @param int $id ID du véhicule à modifier
     * @param array $donnees Données du formulaire
     * @param array $nouvellesImages Fichiers d'images (format $_FILES)
     * @param array $imagesAConserver Liste des chemins d'images existantes à conserver (déjà dans l'ordre souhaité)
     * @param int $couvertureNouvelleIndex Index de la nouvelle image qui doit être couverture (-1 si aucune)
     * @return array|false Véhicule modifié ou false si échec
     */
    public function modifier($id, $donnees, $nouvellesImages = [], $imagesAConserver = [], $couvertureNouvelleIndex = -1) {
        // 0. Vérifier le Rate Limit pour l'upload
        if (!GestionnaireLimiteTaux::verifierTentative('upload')) {
            throw new Exception("Limite d'upload atteinte. Veuillez patienter.");
        }
        GestionnaireLimiteTaux::ajouterTentative('upload');

        // 1. Récupérer le véhicule actuel
        $vehiculeActuel = $this->obtenirParId($id);
        if (!$vehiculeActuel) {
            throw new Exception("Véhicule introuvable.");
        }

        // 2. Préparer les nouvelles images
        $imagesATraiter = [];
        if (isset($nouvellesImages['name']) && is_array($nouvellesImages['name'])) {
            $count = count($nouvellesImages['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($nouvellesImages['error'][$i] === UPLOAD_ERR_OK) {
                    $imagesATraiter[] = [
                        'name' => $nouvellesImages['name'][$i],
                        'type' => $nouvellesImages['type'][$i],
                        'tmp_name' => $nouvellesImages['tmp_name'][$i],
                        'error' => $nouvellesImages['error'][$i],
                        'size' => $nouvellesImages['size'][$i]
                    ];
                }
            }
        } elseif (isset($nouvellesImages['tmp_name']) && !is_array($nouvellesImages['tmp_name'])) {
            if ($nouvellesImages['error'] === UPLOAD_ERR_OK) {
                $imagesATraiter[] = $nouvellesImages;
            }
        }

        // Valider chaque nouvelle image
        foreach ($imagesATraiter as $img) {
            $res = ServiceValidationFichier::validerFichier($img);
            if (!$res['valide']) {
                throw new Exception("Erreur image : " . $res['erreur']);
            }
        }

        $this->connexion->beginTransaction();

        try {
            // 3. Mise à jour des données du véhicule
            $sql = "UPDATE vehicles SET 
                        marque = ?, 
                        modele = ?, 
                        annee = ?, 
                        prix = ?, 
                        km = ?, 
                        carburant = ?, 
                        boite = ?, 
                        description = ?, 
                        ville = ?
                    WHERE id = ?";
            
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
                $id
            ]);

            // 4. Gestion des images existantes - supprimer celles qui ne sont plus gardées
            $imagesActuelles = $vehiculeActuel['images'] ?? [];
            foreach ($imagesActuelles as $imgPath) {
                if (!in_array($imgPath, $imagesAConserver)) {
                    // Supprimer le fichier physique
                    $cheminComplet = __DIR__ . '/../../public' . $imgPath;
                    if (file_exists($cheminComplet)) {
                        @unlink($cheminComplet);
                    }
                    // Supprimer de la base
                    $stmtDel = $this->connexion->prepare("DELETE FROM vehicle_images WHERE vehicle_id = ? AND image_path = ?");
                    $stmtDel->execute([$id, $imgPath]);
                }
            }

            // 5. Ajouter les nouvelles images
            $nouvellesChemins = [];
            foreach ($imagesATraiter as $img) {
                $res = ServiceValidationFichier::deplacerImageVehicule($img, $id);
                if ($res['valide']) {
                    $nouvellesChemins[] = $res['chemin'];
                    // Insérer dans vehicle_images
                    $stmtImg = $this->connexion->prepare("INSERT INTO vehicle_images (vehicle_id, image_path) VALUES (?, ?)");
                    $stmtImg->execute([$id, $res['chemin']]);
                }
            }

            // 6. Déterminer l'image principale
            // Si une nouvelle image est désignée comme couverture
            $imagePrincipale = null;
            
            if ($couvertureNouvelleIndex >= 0 && isset($nouvellesChemins[$couvertureNouvelleIndex])) {
                // Une nouvelle image est la couverture
                $imagePrincipale = $nouvellesChemins[$couvertureNouvelleIndex];
            } elseif (!empty($imagesAConserver)) {
                // La première image existante conservée est la couverture (déjà réorganisée côté client)
                $imagePrincipale = $imagesAConserver[0];
            } elseif (!empty($nouvellesChemins)) {
                // Sinon la première nouvelle image
                $imagePrincipale = $nouvellesChemins[0];
            }
            
            if ($imagePrincipale) {
                $stmtUpdate = $this->connexion->prepare("UPDATE vehicles SET image_path = ? WHERE id = ?");
                $stmtUpdate->execute([$imagePrincipale, $id]);
            } else {
                // Pas d'images - mettre NULL
                $stmtUpdate = $this->connexion->prepare("UPDATE vehicles SET image_path = NULL WHERE id = ?");
                $stmtUpdate->execute([$id]);
            }

            $this->connexion->commit();
            return $this->obtenirParId($id);

        } catch (Throwable $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }
}
