<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * SERVICE DE VALIDATION ET GESTION DES FICHIERS UPLOADÉS
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère la validation, le déplacement et la suppression des fichiers uploadés
 * (images de véhicules et avatars utilisateurs).
 * 
 * Structure des uploads : public/uploads/
 *   ├── vehicules/{id}/     # Images des véhicules (par ID)
 *   └── avatars/            # Avatars utilisateurs
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ServiceValidationFichier {
    
    /** Taille maximale autorisée (5 Mo) */
    const TAILLE_MAX = 5 * 1024 * 1024;
    
    /** Types MIME autorisés */
    const TYPES_AUTORISES = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    
    /** Dossier racine pour les uploads (relatif à public/) */
    const DOSSIER_UPLOADS = 'uploads/';

    /**
     * Obtient l'extension sécurisée d'un fichier (depuis MIME type)
     * Ignore le nom de fichier pour éviter les caractères spéciaux
     * 
     * @param array $fichier Tableau $_FILES
     * @return string Extension (jpg, png, webp)
     */
    private static function obtenirExtensionSecurisee($fichier) {
        // Priorité au type MIME (plus sûr)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $typeMime = $finfo->file($fichier['tmp_name']);
        
        // Mapper le MIME vers l'extension
        $mappingMime = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];
        
        if (isset($mappingMime[$typeMime])) {
            return $mappingMime[$typeMime];
        }
        
        // Fallback : extraire depuis le nom (nettoyé)
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
        $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($extension, $extensionsAutorisees)) {
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }
        
        // Par défaut
        return 'jpg';
    }

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
     * Format : vehicule_{vehiculeId}_{date}_{random}.{ext}
     * 
     * @param array $fichier Tableau $_FILES
     * @param int $idVehicule ID du véhicule
     * @return array ['valide' => bool, 'chemin' => string, 'erreur' => string]
     */
    public static function deplacerImageVehicule($fichier, $idVehicule) {
        $validation = self::validerFichier($fichier);
        if (!$validation['valide']) return $validation;

        // Obtenir l'extension sécurisée (ignore le nom original)
        $extension = self::obtenirExtensionSecurisee($fichier);
        
        // Format : vehicule_32_20260103_a1b2c3d4.jpg
        $date = date('Ymd');
        $random = substr(bin2hex(random_bytes(4)), 0, 8);
        $nomUnique = "vehicule_{$idVehicule}_{$date}_{$random}.{$extension}";
        
        // Structure : public/uploads/vehicules/{id}/
        $cheminRelatifDossier = 'uploads/vehicules/' . $idVehicule . '/';
        
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
     * Format : avatar_user{userId}_{date}_{random}.{ext}
     * 
     * @param array $fichier Tableau $_FILES
     * @param int $userId ID de l'utilisateur (optionnel)
     * @return array ['valide' => bool, 'chemin' => string, 'erreur' => string]
     */
    public static function deplacerAvatar($fichier, $userId = null) {
        $validation = self::validerFichier($fichier);
        if (!$validation['valide']) return $validation;

        // Obtenir l'extension sécurisée
        $extension = self::obtenirExtensionSecurisee($fichier);
        
        // Format : avatar_user3_20260103_a1b2c3d4.jpg
        $date = date('Ymd');
        $random = substr(bin2hex(random_bytes(4)), 0, 8);
        $userPart = $userId ? "user{$userId}_" : '';
        $nomUnique = "avatar_{$userPart}{$date}_{$random}.{$extension}";
        
        $cheminRelatifDossier = 'uploads/avatars/';
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
     * 
     * @param int $idVehicule ID du véhicule
     * @return bool Succès de la suppression
     */
    public static function supprimerDossierVehicule($idVehicule) {
        $racineProjet = dirname(__DIR__, 2); 
        $dossierCible = $racineProjet . '/public/uploads/vehicules/' . $idVehicule . '/';

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
