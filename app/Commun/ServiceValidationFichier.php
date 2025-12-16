<?php

class ServiceValidationFichier {
    
    const TAILLE_MAX = 5 * 1024 * 1024; // 5 Mo
    const TYPES_AUTORISES = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    
    // Nouveau dossier racine pour le stockage public
    const DOSSIER_RACINE = 'public/stockage/';

    /**
     * Valide un fichier image (sans le déplacer)
     */
    public static function validerFichier($fichier) {
        if ($fichier['error'] !== UPLOAD_ERR_OK) {
            return ['valide' => false, 'erreur' => 'Erreur lors du téléchargement (Code ' . $fichier['error'] . ')'];
        }

        if ($fichier['size'] > self::TAILLE_MAX) {
            return ['valide' => false, 'erreur' => 'L\'image est trop volumineuse (Max 5Mo)'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $typeMime = $finfo->file($fichier['tmp_name']);

        if (!in_array($typeMime, self::TYPES_AUTORISES)) {
            // Vérifier aussi l'extension du fichier
            $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
            $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (!in_array($extension, $extensionsAutorisees)) {
                return ['valide' => false, 'erreur' => 'Format d\'image non autorisé. Seuls les formats JPEG, PNG et WebP sont acceptés.'];
            }
        }

        return ['valide' => true];
    }

    /**
     * Déplace une image de véhicule dans son dossier spécifique
     */
    public static function deplacerImageVehicule($fichier, $idVehicule) {
        $validation = self::validerFichier($fichier);
        if (!$validation['valide']) return $validation;

        $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
        if (empty($extension)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $typeMime = $finfo->file($fichier['tmp_name']);
            $extension = str_replace('image/', '', $typeMime);
        }
        
        $nomUnique = uniqid('v_', true) . '.' . $extension;
        
        // Structure : public/stockage/vehicule/{id}/
        $cheminRelatifDossier = 'stockage/vehicule/' . $idVehicule . '/';
        
        // Chemin absolu
        $racineProjet = dirname(__DIR__, 2); 
        $dossierCible = $racineProjet . '/public/' . $cheminRelatifDossier;

        if (!is_dir($dossierCible)) {
            mkdir($dossierCible, 0755, true);
        }

        $cheminCible = $dossierCible . $nomUnique;

        if (move_uploaded_file($fichier['tmp_name'], $cheminCible)) {
            return [
                'valide' => true, 
                'chemin' => $cheminRelatifDossier . $nomUnique
            ];
        } else {
            return ['valide' => false, 'erreur' => 'Impossible de déplacer le fichier.'];
        }
    }

    /**
     * Déplace un avatar dans le dossier commun
     */
    public static function deplacerAvatar($fichier) {
        $validation = self::validerFichier($fichier);
        if (!$validation['valide']) return $validation;

        $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
        $nomUnique = 'avatar_' . time() . '_' . uniqid() . '.' . $extension;
        
        $cheminRelatifDossier = 'stockage/avatar/';
        $racineProjet = dirname(__DIR__, 2); 
        $dossierCible = $racineProjet . '/public/' . $cheminRelatifDossier;

        if (!is_dir($dossierCible)) {
            mkdir($dossierCible, 0755, true);
        }

        if (move_uploaded_file($fichier['tmp_name'], $dossierCible . $nomUnique)) {
            return [
                'valide' => true, 
                'chemin' => $cheminRelatifDossier . $nomUnique
            ];
        }
        
        return ['valide' => false, 'erreur' => 'Erreur déplacement avatar'];
    }

    /**
     * Ancienne méthode pour rétrocompatibilité (ou à supprimer)
     * @deprecated Utiliser deplacerImageVehicule ou deplacerAvatar
     */
    public static function validerImageVehicule($fichier) {
        // Redirige vers une version simplifiée qui met tout dans un dossier vrac si besoin
        // Mais pour ce refactoring, on va modifier les appelants.
        return self::deplacerImageVehicule($fichier, 'temp'); 
    }

    /**
     * Supprime le dossier de stockage d'un véhicule et tout son contenu
     */
    public static function supprimerDossierVehicule($idVehicule) {
        $racineProjet = dirname(__DIR__, 2); 
        $dossierCible = $racineProjet . '/public/stockage/vehicule/' . $idVehicule . '/';

        if (!is_dir($dossierCible)) {
            return true; // Le dossier n'existe pas, donc c'est "supprimé"
        }

        $fichiers = glob($dossierCible . '*', GLOB_MARK);
        foreach ($fichiers as $fichier) {
            if (is_file($fichier)) {
                unlink($fichier);
            }
        }
        
        return rmdir($dossierCible);
    }
}
