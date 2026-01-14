<?php

class ModeleVehicule {
    private $connexion;

    public function __construct() {
        $this->connexion = BaseDeDonnees::obtenirConnexion();
    }

    public function obtenirTous($filtres = []) {
        $sql = "SELECT v.*, v.type_vehicule,
                       u.first_name as seller_first_name, u.last_name as seller_last_name,
                       u.email as seller_email, u.phone as seller_phone
                FROM vehicles v
                LEFT JOIN users u ON u.id = v.user_id";
        
        $params = [];
        $conditions = [];

        // Par défaut, n'afficher que les annonces publiques
        // Sauf si l'utilisateur demande ses propres annonces
        if (isset($filtres['include_private']) && isset($filtres['user_id'])) {
            // L'utilisateur peut voir ses propres annonces privées
            $conditions[] = "(v.status = 'public' OR (v.status = 'prive' AND v.user_id = :owner_id))";
            $params[':owner_id'] = $filtres['user_id'];
        } elseif (!isset($filtres['all_status'])) {
            // Par défaut, seulement les annonces publiques
            $conditions[] = "v.status = 'public'";
        }

        // Recherche textuelle
        if (!empty($filtres['recherche'])) {
            $conditions[] = "(v.marque LIKE :q OR v.modele LIKE :q)";
            $params[':q'] = "%" . $filtres['recherche'] . "%";
        }

        // Type de véhicule
        if (!empty($filtres['type'])) {
            $conditions[] = "v.type_vehicule = :type";
            $params[':type'] = $filtres['type'];
        }

        // Marque
        if (!empty($filtres['marque'])) {
            $conditions[] = "v.marque = :marque";
            $params[':marque'] = $filtres['marque'];
        }

        // Prix Min
        if (!empty($filtres['prix_min'])) {
            $conditions[] = "v.prix >= :prix_min";
            $params[':prix_min'] = $filtres['prix_min'];
        }

        // Prix Max
        if (!empty($filtres['prix_max'])) {
            $conditions[] = "v.prix <= :prix_max";
            $params[':prix_max'] = $filtres['prix_max'];
        }

        // Année Min
        if (!empty($filtres['annee_min'])) {
            $conditions[] = "v.annee >= :annee_min";
            $params[':annee_min'] = $filtres['annee_min'];
        }

        // Année Max
        if (!empty($filtres['annee_max'])) {
            $conditions[] = "v.annee <= :annee_max";
            $params[':annee_max'] = $filtres['annee_max'];
        }

        // Carburant (Array)
        if (!empty($filtres['carburant']) && is_array($filtres['carburant'])) {
            $placeholders = [];
            foreach ($filtres['carburant'] as $k => $val) {
                $key = ":carburant_$k";
                $placeholders[] = $key;
                $params[$key] = $val;
            }
            $conditions[] = "v.carburant IN (" . implode(',', $placeholders) . ")";
        }

        // Boîte (Array)
        if (!empty($filtres['boite']) && is_array($filtres['boite'])) {
            $placeholders = [];
            foreach ($filtres['boite'] as $k => $val) {
                $key = ":boite_$k";
                $placeholders[] = $key;
                $params[$key] = $val;
            }
            $conditions[] = "v.boite IN (" . implode(',', $placeholders) . ")";
        }

        // État (Array)
        if (!empty($filtres['etat']) && is_array($filtres['etat'])) {
            $placeholders = [];
            foreach ($filtres['etat'] as $k => $val) {
                $key = ":etat_$k";
                $placeholders[] = $key;
                $params[$key] = $val;
            }
            $conditions[] = "v.etat IN (" . implode(',', $placeholders) . ")";
        }

        // Crit'Air (Array)
        if (!empty($filtres['crit_air']) && is_array($filtres['crit_air'])) {
            $placeholders = [];
            foreach ($filtres['crit_air'] as $k => $val) {
                $key = ":crit_air_$k";
                $placeholders[] = $key;
                $params[$key] = $val;
            }
            $conditions[] = "v.crit_air IN (" . implode(',', $placeholders) . ")";
        }

        // Nombre de portes (Array)
        if (!empty($filtres['nb_portes']) && is_array($filtres['nb_portes'])) {
            $placeholders = [];
            foreach ($filtres['nb_portes'] as $k => $val) {
                $key = ":nb_portes_$k";
                $placeholders[] = $key;
                $params[$key] = $val;
            }
            $conditions[] = "v.nb_portes IN (" . implode(',', $placeholders) . ")";
        }

        // Contrôle technique (Array)
        if (!empty($filtres['controle_technique']) && is_array($filtres['controle_technique'])) {
            $placeholders = [];
            foreach ($filtres['controle_technique'] as $k => $val) {
                $key = ":controle_technique_$k";
                $placeholders[] = $key;
                $params[$key] = $val;
            }
            $conditions[] = "v.controle_technique IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Tri personnalisé
        $triValides = ['created_at', 'prix', 'annee', 'km', 'score_ia', 'views_count'];
        $tri = 'created_at';
        $ordre = 'DESC';
        
        if (!empty($filtres['tri']) && in_array($filtres['tri'], $triValides)) {
            $tri = $filtres['tri'];
        }
        if (!empty($filtres['ordre']) && in_array(strtoupper($filtres['ordre']), ['ASC', 'DESC'])) {
            $ordre = strtoupper($filtres['ordre']);
        }
        
        // Pour le score_ia, mettre les NULL à la fin
        if ($tri === 'score_ia') {
            $sql .= " ORDER BY v.score_ia IS NULL, v.score_ia $ordre, v.created_at DESC";
        } else {
            $sql .= " ORDER BY v.$tri $ordre";
        }
        
        // Limite
        if (!empty($filtres['limit']) && is_numeric($filtres['limit'])) {
            $sql .= " LIMIT " . intval($filtres['limit']);
        }

        $stmt = $this->connexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenirParId($id) {
        $sql = "SELECT v.*, 
                       u.first_name as seller_first_name, u.last_name as seller_last_name,
                       u.email as seller_email, u.phone as seller_phone, u.avatar_path as seller_avatar,
                       u.hide_phone as seller_hide_phone
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

        // 2. Récupération des coordonnées GPS à partir de la ville/code postal
        $latitude = null;
        $longitude = null;
        if (!empty($donnees['ville']) || !empty($donnees['code_postal'])) {
            $modeleLocalisation = new ModeleLocalisation();
            $coordonnees = $modeleLocalisation->obtenirCoordonnees(
                $donnees['ville'] ?? '',
                $donnees['code_postal'] ?? null
            );
            if ($coordonnees && isset($coordonnees['lat'], $coordonnees['lon'])) {
                $latitude = (float)$coordonnees['lat'];
                $longitude = (float)$coordonnees['lon'];
            }
        }

        // 3. Insertion du véhicule (Initialement sans image)
            $sql = "INSERT INTO vehicles (
                        type_vehicule, marque, modele, annee, prix, km, carburant, boite, 
                        description, code_postal, ville, user_id, image_path, latitude, longitude,
                        etat, crit_air, provenance, controle_technique, couleur, nb_portes, nb_places,
                        longueur, largeur, hauteur, taille_coffre, puissance_cv, norme_euro, consommation, consommation_secondaire, type_hybride, emission_co2, autonomie
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";
        
        $this->connexion->beginTransaction();
        
        try {
            $stmt = $this->connexion->prepare($sql);
            $stmt->execute([
                $donnees['type_vehicule'] ?? 'voiture',
                $donnees['marque'],
                $donnees['modele'],
                (int)$donnees['annee'],
                (float)$donnees['prix'],
                (int)($donnees['km'] ?? 0),
                $donnees['carburant'] ?? '',
                $donnees['boite'] ?? '',
                $donnees['description'] ?? '',
                $donnees['code_postal'] ?? null,
                $donnees['ville'] ?? '',
                $userId,
                // image_path = NULL (pas de valeur ici, défini dans le SQL)
                $latitude,
                $longitude,
                $donnees['etat'] ?: null,
                $donnees['crit_air'] ?: null,
                $donnees['provenance'] ?: null,
                $donnees['controle_technique'] ?? 'non_requis',
                $donnees['couleur'] ?: null,
                isset($donnees['nb_portes']) && $donnees['nb_portes'] ? (int)$donnees['nb_portes'] : null,
                isset($donnees['nb_places']) && $donnees['nb_places'] ? (int)$donnees['nb_places'] : null,
                isset($donnees['longueur']) && $donnees['longueur'] ? (float)$donnees['longueur'] : null,
                isset($donnees['largeur']) && $donnees['largeur'] ? (float)$donnees['largeur'] : null,
                isset($donnees['hauteur']) && $donnees['hauteur'] ? (float)$donnees['hauteur'] : null,
                isset($donnees['taille_coffre']) && $donnees['taille_coffre'] ? $donnees['taille_coffre'] : null,
                isset($donnees['puissance_cv']) && $donnees['puissance_cv'] ? (int)$donnees['puissance_cv'] : null,
                $donnees['norme_euro'] ?: null,
                isset($donnees['consommation']) && $donnees['consommation'] ? (float)$donnees['consommation'] : null,
                isset($donnees['consommation_secondaire']) && $donnees['consommation_secondaire'] ? (float)$donnees['consommation_secondaire'] : null,
                $donnees['type_hybride'] ?: null,
                isset($donnees['emission_co2']) && $donnees['emission_co2'] ? (int)$donnees['emission_co2'] : null,
                isset($donnees['autonomie']) && $donnees['autonomie'] ? (int)$donnees['autonomie'] : null
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

        // 📧 Notifier les utilisateurs qui avaient ce véhicule en favoris AVANT suppression
        $this->notifierUtilisateursFavoris($id, $vehicule['brand'], $vehicule['model']);

        // Suppression du dossier physique des photos
        ServiceValidationFichier::supprimerDossierVehicule($id);

        // Suppression en base (les favoris seront supprimés par CASCADE)
        $stmt = $this->connexion->prepare("DELETE FROM vehicles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Notifie par email tous les utilisateurs ayant un véhicule en favoris
     * que celui-ci n'est plus disponible
     */
    private function notifierUtilisateursFavoris($idVehicule, $marque, $modele) {
        try {
            $modeleFavoris = new ModeleFavoris();
            $utilisateurs = $modeleFavoris->obtenirUtilisateursAvecFavori($idVehicule);
            
            foreach ($utilisateurs as $utilisateur) {
                try {
                    ServiceEmail::envoyerNotificationFavoriSupprime(
                        $utilisateur['email'],
                        $utilisateur['first_name'],
                        $marque,
                        $modele
                    );
                } catch (Exception $e) {
                    // Log l'erreur mais continue pour les autres utilisateurs
                    error_log('Erreur envoi notification favori supprimé à ' . $utilisateur['email'] . ': ' . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            // On log l'erreur mais on ne bloque pas la suppression
            error_log('Erreur notification favoris: ' . $e->getMessage());
        }
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

        // Vérifier le Rate Limit uniquement s'il y a de nouvelles images à uploader
        if (!empty($imagesATraiter)) {
            if (!GestionnaireLimiteTaux::verifierTentative('upload')) {
                throw new Exception("Limite d'upload atteinte. Veuillez patienter.");
            }
            // Compter le nombre réel d'images uploadées (plus précis)
            GestionnaireLimiteTaux::ajouterTentative('upload', count($imagesATraiter));
        }

        // Valider chaque nouvelle image
        foreach ($imagesATraiter as $img) {
            $res = ServiceValidationFichier::validerFichier($img);
            if (!$res['valide']) {
                throw new Exception("Erreur image : " . $res['erreur']);
            }
        }

        // Récupération des coordonnées GPS à partir de la ville/code postal
        $latitude = null;
        $longitude = null;
        if (!empty($donnees['ville']) || !empty($donnees['code_postal'])) {
            $modeleLocalisation = new ModeleLocalisation();
            $coordonnees = $modeleLocalisation->obtenirCoordonnees(
                $donnees['ville'] ?? '',
                $donnees['code_postal'] ?? null
            );
            if ($coordonnees && isset($coordonnees['lat'], $coordonnees['lon'])) {
                $latitude = (float)$coordonnees['lat'];
                $longitude = (float)$coordonnees['lon'];
            }
        }

        $this->connexion->beginTransaction();

        try {
            // 3. Mise à jour des données du véhicule (TOUS les champs)
            $sql = "UPDATE vehicles SET 
                        type_vehicule = ?,
                        marque = ?, 
                        modele = ?, 
                        annee = ?, 
                        prix = ?, 
                        km = ?, 
                        carburant = ?, 
                        boite = ?, 
                        description = ?, 
                        code_postal = ?,
                        ville = ?,
                        latitude = ?,
                        longitude = ?,
                        etat = ?,
                        crit_air = ?,
                        provenance = ?,
                        controle_technique = ?,
                        couleur = ?,
                        nb_portes = ?,
                        nb_places = ?,
                        longueur = ?,
                        largeur = ?,
                        hauteur = ?,
                        taille_coffre = ?,
                        puissance_cv = ?,
                        norme_euro = ?,
                        consommation = ?,
                        consommation_secondaire = ?,
                        type_hybride = ?,
                        emission_co2 = ?,
                        autonomie = ?
                    WHERE id = ?";
            
            $stmt = $this->connexion->prepare($sql);
            $stmt->execute([
                $donnees['type_vehicule'] ?? 'voiture',
                $donnees['marque'],
                $donnees['modele'],
                (int)$donnees['annee'],
                (float)$donnees['prix'],
                (int)($donnees['km'] ?? 0),
                $donnees['carburant'] ?? '',
                $donnees['boite'] ?? '',
                $donnees['description'] ?? '',
                $donnees['code_postal'] ?? null,
                $donnees['ville'] ?? '',
                $latitude,
                $longitude,
                $donnees['etat'] ?: null,
                $donnees['crit_air'] ?: null,
                $donnees['provenance'] ?: null,
                $donnees['controle_technique'] ?? 'non_requis',
                $donnees['couleur'] ?: null,
                $donnees['nb_portes'] ? (int)$donnees['nb_portes'] : null,
                $donnees['nb_places'] ? (int)$donnees['nb_places'] : null,
                isset($donnees['longueur']) && $donnees['longueur'] ? (float)$donnees['longueur'] : null,
                isset($donnees['largeur']) && $donnees['largeur'] ? (float)$donnees['largeur'] : null,
                isset($donnees['hauteur']) && $donnees['hauteur'] ? (float)$donnees['hauteur'] : null,
                $donnees['taille_coffre'] ?: null,
                $donnees['puissance_cv'] ? (int)$donnees['puissance_cv'] : null,
                $donnees['norme_euro'] ?: null,
                isset($donnees['consommation']) && $donnees['consommation'] ? (float)$donnees['consommation'] : null,
                isset($donnees['consommation_secondaire']) && $donnees['consommation_secondaire'] ? (float)$donnees['consommation_secondaire'] : null,
                $donnees['type_hybride'] ?: null,
                isset($donnees['emission_co2']) && $donnees['emission_co2'] ? (int)$donnees['emission_co2'] : null,
                isset($donnees['autonomie']) && $donnees['autonomie'] ? (int)$donnees['autonomie'] : null,
                $id
            ]);

            // 4. Gestion des images existantes - supprimer celles qui ne sont plus gardées
            $imagesActuelles = $vehiculeActuel['images'] ?? [];
            $imagePrincipaleActuelle = $vehiculeActuel['image_path'] ?? null;
            
            foreach ($imagesActuelles as $imgPath) {
                if (!in_array($imgPath, $imagesAConserver)) {
                    // Supprimer le fichier physique
                    // Le chemin stocké est "uploads/vehicules/XX/image.jpg" (sans / au début)
                    $cheminComplet = __DIR__ . '/../../public/' . ltrim($imgPath, '/');
                    if (file_exists($cheminComplet)) {
                        unlink($cheminComplet);
                    }
                    // Supprimer de la table vehicle_images
                    $stmtDel = $this->connexion->prepare("DELETE FROM vehicle_images WHERE vehicle_id = ? AND image_path = ?");
                    $stmtDel->execute([$id, $imgPath]);
                    
                    // Si c'était l'image principale, la mettre à NULL
                    if ($imgPath === $imagePrincipaleActuelle) {
                        $stmtNullify = $this->connexion->prepare("UPDATE vehicles SET image_path = NULL WHERE id = ?");
                        $stmtNullify->execute([$id]);
                    }
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
            
            // 7. Repasser l'annonce en vérification après modification
            // Les annonces public, prive OU refuse modifiées repassent en en_attente
            if (in_array($vehiculeActuel['status'], ['public', 'prive', 'refuse'])) {
                $stmtStatus = $this->connexion->prepare("UPDATE vehicles SET status = 'en_attente', raison_refus = NULL WHERE id = ?");
                $stmtStatus->execute([$id]);
            }

            $this->connexion->commit();
            return $this->obtenirParId($id);

        } catch (Throwable $e) {
            $this->connexion->rollBack();
            throw $e;
        }
    }

    /**
     * Obtenir les véhicules d'un utilisateur avec leurs statistiques
     */
    public function obtenirParUtilisateurAvecStats($userId) {
        $sql = "SELECT v.id, v.type_vehicule, v.marque, v.modele, v.annee, v.prix, v.km, 
                       v.ville, v.code_postal, v.image_path, v.status, v.created_at,
                       v.views_count, v.contacts_count, v.favorites_count, v.raison_refus,
                       (SELECT COUNT(*) FROM favorites f WHERE f.vehicle_id = v.id) as favorites_live,
                       (SELECT COUNT(*) FROM conversations c WHERE c.vehicle_id = v.id) as contacts_live
                FROM vehicles v
                WHERE v.user_id = ?
                ORDER BY v.created_at DESC";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les statistiques détaillées d'un véhicule
     */
    public function obtenirStatistiquesVehicule($vehicleId, $userId) {
        $sql = "SELECT v.id, v.views_count, v.contacts_count, v.favorites_count,
                       (SELECT COUNT(*) FROM favorites f WHERE f.vehicle_id = v.id) as favorites_live,
                       (SELECT COUNT(*) FROM conversations c WHERE c.vehicle_id = v.id) as contacts_live
                FROM vehicles v
                WHERE v.id = ? AND v.user_id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$vehicleId, $userId]);
        return $stmt->fetch();
    }

    /**
     * Changer le statut d'un véhicule (public/privé)
     */
    public function changerStatut($vehicleId, $userId, $nouveauStatut) {
        $sql = "UPDATE vehicles SET status = ? WHERE id = ? AND user_id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$nouveauStatut, $vehicleId, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Incrémenter le compteur de vues
     */
    public function incrementerVues($vehicleId) {
        $sql = "UPDATE vehicles SET views_count = views_count + 1 WHERE id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$vehicleId]);
    }

    /**
     * Incrémenter le compteur de contacts
     */
    public function incrementerContacts($vehicleId) {
        $sql = "UPDATE vehicles SET contacts_count = contacts_count + 1 WHERE id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$vehicleId]);
    }

    /**
     * Mettre à jour le compteur de favoris
     */
    public function mettreAJourFavoris($vehicleId) {
        $sql = "UPDATE vehicles SET favorites_count = (SELECT COUNT(*) FROM favorites WHERE vehicle_id = ?) WHERE id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$vehicleId, $vehicleId]);
    }

    /**
     * Vérifier si un véhicule est accessible par un utilisateur
     */
    public function estAccessible($vehicleId, $userId = null, $isAdmin = false) {
        $sql = "SELECT user_id, status FROM vehicles WHERE id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$vehicleId]);
        $vehicule = $stmt->fetch();
        
        if (!$vehicule) {
            return false;
        }
        
        // Public = accessible par tous
        if ($vehicule['status'] === 'public') {
            return true;
        }
        
        // En attente ou refusé = accessible seulement par propriétaire ou admin
        if ($vehicule['status'] === 'en_attente' || $vehicule['status'] === 'refuse') {
            if ($isAdmin || ($userId && $vehicule['user_id'] == $userId)) {
                return true;
            }
            return false;
        }
        
        // Privé = accessible par propriétaire ou admin
        if ($isAdmin || ($userId && $vehicule['user_id'] == $userId)) {
            return true;
        }
        
        return false;
    }

    /**
     * Re-soumettre un véhicule refusé pour vérification
     */
    public function resoumettrePourVerification($vehicleId, $userId) {
        // Vérifier que le véhicule appartient à l'utilisateur et est refusé
        $sql = "SELECT status FROM vehicles WHERE id = ? AND user_id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$vehicleId, $userId]);
        $vehicule = $stmt->fetch();
        
        if (!$vehicule) {
            return ['success' => false, 'message' => 'Véhicule non trouvé'];
        }
        
        if ($vehicule['status'] !== 'refuse') {
            return ['success' => false, 'message' => 'Seules les annonces refusées peuvent être re-soumises'];
        }
        
        // Mettre à jour le statut et effacer la raison du refus
        $sql = "UPDATE vehicles SET status = 'en_attente', raison_refus = NULL, updated_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$vehicleId, $userId]);
        
        return ['success' => $stmt->rowCount() > 0, 'message' => 'Annonce re-soumise pour vérification'];
    }

    /**
     * Vérifier si un véhicule peut être modifié par son propriétaire
     */
    public function peutEtreModifie($vehicleId, $userId) {
        $sql = "SELECT status FROM vehicles WHERE id = ? AND user_id = ?";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([$vehicleId, $userId]);
        $vehicule = $stmt->fetch();
        
        if (!$vehicule) {
            return false;
        }
        
        // Impossible de modifier si en attente de vérification
        if ($vehicule['status'] === 'en_attente') {
            return false;
        }
        
        return true;
    }
}
