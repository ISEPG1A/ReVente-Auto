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
 * 🔒 SÉCURITÉ :
 * - Vérification MIME type réel (pas extension)
 * - Noms de fichiers générés (pas user input)
 * - Protection path traversal avec realpath/basename
 * - Liste blanche stricte des types autorisés
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.1 - Sécurité renforcée
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ServiceValidationFichier {
    
    /** Taille maximale autorisée (5 Mo) */
    const TAILLE_MAX = 5 * 1024 * 1024;
    
    /** Types MIME autorisés (liste blanche stricte) */
    const TYPES_AUTORISES = ['image/jpeg', 'image/png', 'image/webp'];
    
    /** Extensions autorisées (pour double vérification) */
    const EXTENSIONS_AUTORISEES = ['jpg', 'jpeg', 'png', 'webp'];
    
    /** Dossier racine pour les uploads (relatif à public/) */
    const DOSSIER_UPLOADS = 'uploads/';
    
    /** Chemin de base sécurisé (calculé une fois) */
    private static $cheminBaseSecurise = null;
    
    /**
     * 🔒 SÉCURITÉ : Obtient le chemin de base sécurisé pour les uploads
     * Utilise realpath pour éviter les path traversal
     * 
     * @return string Chemin absolu vers public/uploads/
     */
    private static function obtenirCheminBaseSecurise(): string {
        if (self::$cheminBaseSecurise === null) {
            $racineProjet = dirname(__DIR__, 2);
            $cheminUploads = $racineProjet . '/public/uploads';
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($cheminUploads)) {
                mkdir($cheminUploads, 0755, true);
            }
            
            // Utiliser realpath pour obtenir le chemin canonique
            self::$cheminBaseSecurise = realpath($cheminUploads);
            
            if (self::$cheminBaseSecurise === false) {
                throw new Exception('Impossible de résoudre le chemin uploads');
            }
        }
        return self::$cheminBaseSecurise;
    }
    
    /**
     * 🔒 SÉCURITÉ : Vérifie qu'un chemin est bien dans le dossier uploads
     * Protection contre path traversal (../)
     * 
     * @param string $chemin Chemin à vérifier
     * @return bool True si le chemin est sécurisé
     */
    private static function verifierCheminSecurise(string $chemin): bool {
        $cheminReel = realpath($chemin);
        if ($cheminReel === false) {
            // Le fichier n'existe pas encore, vérifier le dossier parent
            $dossierParent = realpath(dirname($chemin));
            if ($dossierParent === false) {
                return false;
            }
            return strpos($dossierParent, self::obtenirCheminBaseSecurise()) === 0;
        }
        return strpos($cheminReel, self::obtenirCheminBaseSecurise()) === 0;
    }

    /**
     * 🔒 SÉCURITÉ : Obtient l'extension sécurisée depuis le MIME type réel
     * Ignore complètement le nom de fichier fourni par l'utilisateur
     * 
     * @param array $fichier Tableau $_FILES
     * @return string|null Extension (jpg, png, webp) ou null si invalide
     */
    private static function obtenirExtensionSecurisee($fichier): ?string {
        // Vérifier que le fichier temporaire existe et est uploadé
        if (!isset($fichier['tmp_name']) || !is_uploaded_file($fichier['tmp_name'])) {
            return null;
        }
        
        // Utiliser EXCLUSIVEMENT le type MIME détecté par finfo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $typeMime = $finfo->file($fichier['tmp_name']);
        
        // Mapper le MIME vers l'extension (liste blanche stricte)
        $mappingMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp'
        ];
        
        return $mappingMime[$typeMime] ?? null;
    }
    
    /**
     * 🔒 SÉCURITÉ : Génère un nom de fichier totalement aléatoire et sécurisé
     * N'utilise JAMAIS le nom de fichier fourni par l'utilisateur
     * 
     * @param string $prefixe Préfixe du nom (vehicule, avatar, etc.)
     * @param string $extension Extension du fichier
     * @param int|null $id ID optionnel à inclure
     * @return string Nom de fichier sécurisé
     */
    private static function genererNomSecurise(string $prefixe, string $extension, ?int $id = null): string {
        // Nettoyer le préfixe (alphanumérique uniquement)
        $prefixe = preg_replace('/[^a-zA-Z0-9]/', '', $prefixe);
        
        // Composants du nom
        $date = date('Ymd_His');
        $random = bin2hex(random_bytes(8)); // 16 caractères aléatoires
        
        // Format : prefixe_id_date_random.ext
        if ($id !== null) {
            return sprintf('%s_%d_%s_%s.%s', $prefixe, $id, $date, $random, $extension);
        }
        return sprintf('%s_%s_%s.%s', $prefixe, $date, $random, $extension);
    }

    /**
     * Valide un fichier image (sans le déplacer)
     * 
     * @param array $fichier Tableau $_FILES
     * @return array ['valide' => bool, 'erreur' => string]
     */
    public static function validerFichier($fichier): array {
        // Vérifier les erreurs d'upload
        if (!isset($fichier['error']) || $fichier['error'] !== UPLOAD_ERR_OK) {
            $messagesErreur = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite du serveur',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite du formulaire',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier',
                UPLOAD_ERR_EXTENSION => 'Extension PHP a bloqué le téléchargement'
            ];
            $code = $fichier['error'] ?? UPLOAD_ERR_NO_FILE;
            return ['valide' => false, 'erreur' => $messagesErreur[$code] ?? 'Erreur inconnue'];
        }

        // 🔒 SÉCURITÉ : Vérifier que c'est un vrai fichier uploadé
        if (!is_uploaded_file($fichier['tmp_name'])) {
            return ['valide' => false, 'erreur' => 'Fichier non autorisé'];
        }

        // Vérifier la taille
        if ($fichier['size'] > self::TAILLE_MAX) {
            return ['valide' => false, 'erreur' => 'L\'image est trop volumineuse (Max 5Mo)'];
        }
        
        if ($fichier['size'] === 0) {
            return ['valide' => false, 'erreur' => 'Le fichier est vide'];
        }

        // 🔒 SÉCURITÉ : Vérifier le type MIME réel (pas l'extension !)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $typeMime = $finfo->file($fichier['tmp_name']);

        if (!in_array($typeMime, self::TYPES_AUTORISES, true)) {
            return [
                'valide' => false, 
                'erreur' => 'Format d\'image non autorisé. Seuls JPEG, PNG et WebP sont acceptés.'
            ];
        }
        
        // 🔒 SÉCURITÉ : Vérification supplémentaire - le fichier est-il vraiment une image ?
        $imageInfo = @getimagesize($fichier['tmp_name']);
        if ($imageInfo === false) {
            return ['valide' => false, 'erreur' => 'Le fichier n\'est pas une image valide'];
        }

        return ['valide' => true];
    }

    /**
     * Déplace une image de véhicule dans son dossier spécifique
     * Format : vehicule_{vehiculeId}_{date}_{random}.{ext}
     * 
     * 🔒 SÉCURITÉ : Utilise realpath et vérifie que le chemin reste dans uploads/
     * 
     * @param array $fichier Tableau $_FILES
     * @param int $idVehicule ID du véhicule
     * @return array ['valide' => bool, 'chemin' => string, 'erreur' => string]
     */
    public static function deplacerImageVehicule($fichier, $idVehicule): array {
        // Valider le fichier
        $validation = self::validerFichier($fichier);
        if (!$validation['valide']) {
            return $validation;
        }
        
        // 🔒 SÉCURITÉ : Valider et assainir l'ID du véhicule
        $idVehicule = filter_var($idVehicule, FILTER_VALIDATE_INT);
        if ($idVehicule === false || $idVehicule <= 0) {
            return ['valide' => false, 'erreur' => 'ID véhicule invalide'];
        }

        // Obtenir l'extension depuis le MIME type réel
        $extension = self::obtenirExtensionSecurisee($fichier);
        if ($extension === null) {
            return ['valide' => false, 'erreur' => 'Type de fichier non autorisé'];
        }
        
        // Générer un nom de fichier sécurisé
        $nomUnique = self::genererNomSecurise('vehicule', $extension, $idVehicule);
        
        // Construire le chemin de destination sécurisé
        $cheminBase = self::obtenirCheminBaseSecurise();
        $dossierVehicule = $cheminBase . DIRECTORY_SEPARATOR . 'vehicules' . DIRECTORY_SEPARATOR . $idVehicule;
        
        // Créer le dossier si nécessaire
        if (!is_dir($dossierVehicule)) {
            if (!mkdir($dossierVehicule, 0755, true)) {
                return ['valide' => false, 'erreur' => 'Impossible de créer le dossier'];
            }
        }
        
        $cheminCible = $dossierVehicule . DIRECTORY_SEPARATOR . $nomUnique;
        
        // 🔒 SÉCURITÉ : Vérifier que le chemin cible est bien dans uploads/
        if (!self::verifierCheminSecurise($cheminCible)) {
            error_log("Tentative path traversal détectée: $cheminCible");
            return ['valide' => false, 'erreur' => 'Chemin de destination non autorisé'];
        }

        // Déplacer le fichier uploadé
        if (move_uploaded_file($fichier['tmp_name'], $cheminCible)) {
            // Retourner le chemin relatif pour stockage en BDD
            $cheminRelatif = 'uploads/vehicules/' . $idVehicule . '/' . $nomUnique;
            return [
                'valide' => true, 
                'chemin' => $cheminRelatif
            ];
        }
        
        return ['valide' => false, 'erreur' => 'Impossible de déplacer le fichier'];
    }

    /**
     * Déplace un avatar dans le dossier commun
     * Format : avatar_user{userId}_{date}_{random}.{ext}
     * 
     * 🔒 SÉCURITÉ : Utilise realpath et vérifie le chemin 
     * @param array $fichier Tableau $_FILES
     * @param int $userId ID de l'utilisateur (optionnel)
     * @return array ['valide' => bool, 'chemin' => string, 'erreur' => string]
     */
    public static function deplacerAvatar($fichier, $userId = null): array {
        // Valider le fichier
        $validation = self::validerFichier($fichier);
        if (!$validation['valide']) {
            return $validation;
        }
        
        // 🔒 SÉCURITÉ : Valider l'ID utilisateur si fourni
        if ($userId !== null) {
            $userId = filter_var($userId, FILTER_VALIDATE_INT);
            if ($userId === false || $userId <= 0) {
                return ['valide' => false, 'erreur' => 'ID utilisateur invalide'];
            }
        }

        // Obtenir l'extension depuis le MIME type réel
        $extension = self::obtenirExtensionSecurisee($fichier);
        if ($extension === null) {
            return ['valide' => false, 'erreur' => 'Type de fichier non autorisé'];
        }
        
        // Générer un nom de fichier sécurisé
        $nomUnique = self::genererNomSecurise('avatar', $extension, $userId);
        
        // Construire le chemin de destination sécurisé
        $cheminBase = self::obtenirCheminBaseSecurise();
        $dossierAvatars = $cheminBase . DIRECTORY_SEPARATOR . 'avatars';
        
        // Créer le dossier si nécessaire
        if (!is_dir($dossierAvatars)) {
            if (!mkdir($dossierAvatars, 0755, true)) {
                return ['valide' => false, 'erreur' => 'Impossible de créer le dossier'];
            }
        }
        
        $cheminCible = $dossierAvatars . DIRECTORY_SEPARATOR . $nomUnique;
        
        // 🔒 SÉCURITÉ : Vérifier que le chemin cible est bien dans uploads/
        if (!self::verifierCheminSecurise($cheminCible)) {
            error_log("Tentative path traversal avatar détectée: $cheminCible");
            return ['valide' => false, 'erreur' => 'Chemin de destination non autorisé'];
        }

        // Déplacer le fichier uploadé
        if (move_uploaded_file($fichier['tmp_name'], $cheminCible)) {
            $cheminRelatif = 'uploads/avatars/' . $nomUnique;
            return [
                'valide' => true, 
                'chemin' => $cheminRelatif
            ];
        }
        
        return ['valide' => false, 'erreur' => 'Erreur déplacement avatar'];
    }

    /**
     * Supprime le dossier de stockage d'un véhicule et tout son contenu
     * 
     * 🔒 SÉCURITÉ : Vérifie que le dossier est bien dans uploads/vehicules/
     * 
     * @param int $idVehicule ID du véhicule
     * @return bool Succès de la suppression
     */
    public static function supprimerDossierVehicule($idVehicule): bool {
        // 🔒 SÉCURITÉ : Valider l'ID
        $idVehicule = filter_var($idVehicule, FILTER_VALIDATE_INT);
        if ($idVehicule === false || $idVehicule <= 0) {
            return false;
        }
        
        $cheminBase = self::obtenirCheminBaseSecurise();
        $dossierCible = $cheminBase . DIRECTORY_SEPARATOR . 'vehicules' . DIRECTORY_SEPARATOR . $idVehicule;

        // 🔒 SÉCURITÉ : Vérifier que le dossier est bien dans uploads/
        if (!is_dir($dossierCible) || !self::verifierCheminSecurise($dossierCible)) {
            return true; // Considéré comme "supprimé" si n'existe pas ou hors zone
        }

        // Supprimer tous les fichiers du dossier
        $fichiers = glob($dossierCible . DIRECTORY_SEPARATOR . '*');
        foreach ($fichiers as $fichier) {
            if (is_file($fichier)) {
                // Double vérification sécurité
                if (self::verifierCheminSecurise($fichier)) {
                    unlink($fichier);
                }
            }
        }
        
        return @rmdir($dossierCible);
    }
    
    /**
     * Supprime un fichier uploadé de manière sécurisée
     * 
     * @param string $cheminRelatif Chemin relatif du fichier (ex: uploads/avatars/xxx.jpg)
     * @return bool Succès de la suppression
     */
    public static function supprimerFichier(string $cheminRelatif): bool {
        // 🔒 SÉCURITÉ : Nettoyer le chemin
        $cheminRelatif = str_replace(['..', "\0"], '', $cheminRelatif);
        
        $racineProjet = dirname(__DIR__, 2);
        $cheminComplet = $racineProjet . '/public/' . $cheminRelatif;
        
        // 🔒 SÉCURITÉ : Vérifier que le fichier est dans uploads/
        if (!self::verifierCheminSecurise($cheminComplet)) {
            return false;
        }
        
        if (file_exists($cheminComplet) && is_file($cheminComplet)) {
            return unlink($cheminComplet);
        }
        
        return true;
    }
}
