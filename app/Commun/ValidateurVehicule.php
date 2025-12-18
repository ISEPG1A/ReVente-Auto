<?php

/**
 * 🔒 VALIDATEUR SÉCURITÉ ULTRA-COMPLET - SOLUTION DÉFINITIVE
 * 
 * Cette classe centralise TOUTES les validations de sécurité pour les véhicules.
 * Utilisation : ValidateurVehicule::valider($donnees, $fichiers, $typeOperation)
 * 
 * Protections implémentées :
 * - Validation ENUM stricte (17 champs)
 * - Cohérence métier stricte (moto, hybride, électrique)
 * - Plages numériques avec minimum/maximum
 * - Protection XSS (sanitisation)
 * - Validation upload (8 contrôles)
 * - Protection injection
 * - Validation type_vehicule/carburant
 */
class ValidateurVehicule {
    
    // 🔒 ENUM : Types de véhicules autorisés
    private const TYPES_VEHICULES = ['moto', 'voiture', 'camion'];
    
    // 🔒 ENUM : Carburants par type de véhicule
    private const CARBURANTS_PAR_TYPE = [
        'moto' => ['Essence', 'Électrique'],
        'voiture' => ['Essence', 'Diesel', 'Hybride', 'Électrique', 'GPL'],
        'camion' => ['Essence', 'Diesel', 'GPL', 'Électrique', 'Hybride']
    ];
    
    // 🔒 ENUM : États du véhicule
    private const ETATS = ['neuf', 'bon', 'moyen', 'mauvais'];
    
    // 🔒 ENUM : Vignettes Crit'Air
    private const CRIT_AIR = ['0', '1', '2', '3', '4', '5'];
    
    // 🔒 ENUM : Contrôle technique
    private const CONTROLE_TECHNIQUE = ['non_requis', 'oui', 'non'];
    
    // 🔒 ENUM : Provenance
    private const PROVENANCES = ['France', 'Europe', 'Import'];
    
    // 🔒 ENUM : Normes Euro
    private const NORMES_EURO = ['Euro 1', 'Euro 2', 'Euro 3', 'Euro 4', 'Euro 5', 'Euro 6', 'Euro 6d'];
    
    // 🔒 ENUM : Taille coffre
    private const TAILLES_COFFRE = ['petit', 'moyen', 'grand'];
    
    // 🔒 ENUM : Types hybrides
    private const TYPES_HYBRIDES = [
        'essence_electrique',
        'diesel_electrique',
        'essence_electrique_rechargeable',
        'diesel_electrique_rechargeable',
        'gpl_essence'
    ];
    
    // 🔒 ENUM : Boîte de vitesse
    private const BOITES = ['Manuelle', 'Automatique'];
    
    // 🔒 ENUM : Nombre de places
    private const NB_PLACES = ['2', '3', '4', '5', '6+'];
    
    // 🔒 Types hybrides avec autonomie
    private const TYPES_AVEC_AUTONOMIE = [
        'essence_electrique_rechargeable',
        'diesel_electrique_rechargeable'
    ];
    
    /**
     * 🔒 MÉTHODE PRINCIPALE : Valider toutes les données du véhicule
     * @param array $donnees Données POST
     * @param array|null $fichiers Fichiers uploadés ($_FILES)
     * @param string $typeOperation 'ajout' ou 'modification'
     * @return array ['valide' => bool, 'erreurs' => array, 'donnees_nettoyees' => array]
     */
    public static function valider($donnees, $fichiers = null, $typeOperation = 'ajout') {
        $erreurs = [];
        
        // 1️⃣ Validation type véhicule et carburant (CRITIQUE)
        // 🔒 SÉCURITÉ : Cast en string pour éviter les erreurs si un tableau est envoyé (type juggling)
        $typeVehicule = (string)($donnees['type_vehicule'] ?? '');
        $carburant = (string)($donnees['carburant'] ?? '');
        
        if (!in_array($typeVehicule, self::TYPES_VEHICULES)) {
            $erreurs[] = 'Type de véhicule invalide.';
            // Arrêt immédiat car toutes les autres validations dépendent du type
            return ['valide' => false, 'erreurs' => $erreurs, 'donnees_nettoyees' => []];
        }
        
        if (!in_array($carburant, self::CARBURANTS_PAR_TYPE[$typeVehicule])) {
            $erreurs[] = "Le carburant '{$carburant}' n'est pas disponible pour le type '{$typeVehicule}'.";
        }
        
        // 🔒 FAILLE #4 : Défense profondeur - Moto ne peut JAMAIS être Hybride
        if ($typeVehicule === 'moto' && $carburant === 'Hybride') {
            $erreurs[] = 'Une moto ne peut pas être hybride.';
        }
        
        // 2️⃣ Validation longueur AVANT sanitisation (FAILLE #3)
        $erreursLongueur = self::validerLongueurs($donnees);
        $erreurs = array_merge($erreurs, $erreursLongueur);
        
        // 3️⃣ Sanitisation XSS (APRÈS validation longueur)
        $donnees = self::sanitiserDonnees($donnees);
        
        // 4️⃣ Validation champs texte (format)
        $erreursTexte = self::validerChampsTexte($donnees);
        $erreurs = array_merge($erreurs, $erreursTexte);
        
        // 4️⃣ Validation champs ENUM
        $erreursEnum = self::validerChampsEnum($donnees, $typeVehicule);
        $erreurs = array_merge($erreurs, $erreursEnum);
        
        // 5️⃣ Validation champs numériques
        $erreursNumeriques = self::validerChampsNumeriques($donnees);
        $erreurs = array_merge($erreurs, $erreursNumeriques);
        
        // 6️⃣ Validation cohérence métier (CRITIQUE)
        $erreursCoherence = self::validerCoherenceMetier($donnees, $typeVehicule, $carburant);
        $erreurs = array_merge($erreurs, $erreursCoherence);
        
        // 7️⃣ Validation images (si upload)
        if ($typeOperation === 'ajout' || ($fichiers && !empty($fichiers['images']['name'][0]))) {
            $erreursImages = self::validerImages($fichiers, $donnees, $typeOperation);
            $erreurs = array_merge($erreurs, $erreursImages);
        }
        
        // 8️⃣ Nettoyer les champs interdits selon type véhicule
        $donnees = self::nettoyerChamps($donnees, $typeVehicule, $carburant);
        
        return [
            'valide' => empty($erreurs),
            'erreurs' => $erreurs,
            'donnees_nettoyees' => $donnees
        ];
    }
    
    /**
     * 🔒 Validation longueurs AVANT sanitisation (FAILLE #3)
     */
    private static function validerLongueurs($donnees) {
        $erreurs = [];
        
        // Longueurs AVANT htmlspecialchars (qui augmente la taille)
        if (isset($donnees['marque']) && (strlen($donnees['marque']) < 2 || strlen($donnees['marque']) > 45)) {
            $erreurs[] = 'Marque invalide (2-45 caractères).';
        }
        if (isset($donnees['modele']) && (strlen($donnees['modele']) < 1 || strlen($donnees['modele']) > 45)) {
            $erreurs[] = 'Modèle invalide (1-45 caractères).';
        }
        if (isset($donnees['ville']) && (strlen($donnees['ville']) < 2 || strlen($donnees['ville']) > 95)) {
            $erreurs[] = 'Ville invalide (2-95 caractères).';
        }
        if (isset($donnees['couleur']) && (strlen($donnees['couleur']) < 2 || strlen($donnees['couleur']) > 45)) {
            $erreurs[] = 'Couleur invalide (2-45 caractères).';
        }
        if (isset($donnees['description']) && mb_strlen($donnees['description']) > 4900) {
            $erreurs[] = 'Description trop longue (maximum 4900 caractères).';
        }
        if (isset($donnees['provenance']) && strlen($donnees['provenance']) > 45) {
            $erreurs[] = 'Provenance invalide (maximum 45 caractères).';
        }
        
        return $erreurs;
    }
    
    /**
     * 📝 Normalisation du texte : première lettre de chaque mot en majuscule, reste en minuscule
     * Gère les mots composés (tirets, apostrophes) correctement
     * Exemple : "MANTES-LA-JOLIE" → "Mantes-La-Jolie", "l'ISLE-ADAM" → "L'Isle-Adam"
     * 
     * @param string $texte Le texte à normaliser
     * @return string Le texte normalisé
     */
    public static function normaliserTexte($texte) {
        if (empty($texte)) {
            return $texte;
        }
        
        // Convertir tout en minuscule d'abord
        $texte = mb_strtolower(trim($texte), 'UTF-8');
        
        // Liste des petits mots à garder en minuscule (sauf en début)
        $motsMinuscules = ['la', 'le', 'les', 'de', 'du', 'des', 'en', 'sur', 'sous', 'au', 'aux'];
        
        // Séparer par espaces pour traiter chaque partie
        $parties = preg_split('/(\s+)/u', $texte, -1, PREG_SPLIT_DELIM_CAPTURE);
        $resultat = [];
        $premierMot = true;
        
        foreach ($parties as $partie) {
            // Si c'est un espace, le garder tel quel
            if (preg_match('/^\s+$/u', $partie)) {
                $resultat[] = $partie;
                continue;
            }
            
            // Vérifier si c'est un petit mot (sauf si c'est le premier)
            if (!$premierMot && in_array($partie, $motsMinuscules)) {
                $resultat[] = $partie;
            } else {
                // Traiter les tirets et apostrophes dans le mot
                $resultat[] = self::capitaliserMotCompose($partie);
            }
            
            $premierMot = false;
        }
        
        return implode('', $resultat);
    }
    
    /**
     * Capitalise chaque partie d'un mot composé (avec tirets ou apostrophes)
     * Exemple : "mantes-la-jolie" → "Mantes-La-Jolie"
     * 
     * @param string $mot Le mot à traiter
     * @return string Le mot avec chaque partie capitalisée
     */
    private static function capitaliserMotCompose($mot) {
        // Traiter les tirets
        $mot = preg_replace_callback('/(?:^|-)([a-zà-ÿ])/u', function($matches) {
            $prefixe = strpos($matches[0], '-') === 0 ? '-' : '';
            return $prefixe . mb_strtoupper($matches[1], 'UTF-8');
        }, $mot);
        
        // Traiter les apostrophes
        $mot = preg_replace_callback("/(?:^|')([a-zà-ÿ])/u", function($matches) {
            $prefixe = strpos($matches[0], "'") === 0 ? "'" : '';
            return $prefixe . mb_strtoupper($matches[1], 'UTF-8');
        }, $mot);
        
        return $mot;
    }
    
    /**
     * 🔒 Sanitisation XSS sur tous les champs texte + normalisation
     */
    private static function sanitiserDonnees($donnees) {
        $champsTexte = ['marque', 'modele', 'ville', 'couleur', 'description', 'provenance'];
        
        // Champs à normaliser (marque, ville, couleur, provenance)
        // EXCEPTION : modele et description ne sont PAS normalisés
        $champsANormaliser = ['marque', 'ville', 'couleur', 'provenance'];
        
        foreach ($champsTexte as $champ) {
            if (isset($donnees[$champ])) {
                // 🔒 SÉCURITÉ : Rejet des tableaux/objets injectés
                if (!is_string($donnees[$champ]) && !is_numeric($donnees[$champ])) {
                    $donnees[$champ] = '';
                }
                
                // 📝 Normalisation AVANT sanitisation HTML (sauf modele et description)
                if (in_array($champ, $champsANormaliser)) {
                    $donnees[$champ] = self::normaliserTexte((string)$donnees[$champ]);
                }
                
                // 🔒 Sanitisation XSS
                $donnees[$champ] = htmlspecialchars((string)$donnees[$champ], ENT_QUOTES, 'UTF-8');
            }
        }
        
        return $donnees;
    }
    
    /**
     * 🔒 Validation champs texte (format après sanitisation)
     */
    private static function validerChampsTexte($donnees) {
        $erreurs = [];
        
        // Marque (obligatoire)
        if (!isset($donnees['marque']) || !Utilitaires::chaineValide($donnees['marque'], 60)) {
            $erreurs[] = 'Marque obligatoire.';
        }
        
        // Modèle (obligatoire)
        if (!isset($donnees['modele']) || !Utilitaires::chaineValide($donnees['modele'], 60)) {
            $erreurs[] = 'Modèle obligatoire.';
        }
        
        // Ville (obligatoire) - Validation stricte (pas de chiffres, symboles limités)
        if (!isset($donnees['ville']) || !Utilitaires::chaineValide($donnees['ville'], 110)) {
            $erreurs[] = 'Ville obligatoire.';
        } elseif (preg_match('/\d/', $donnees['ville'])) {
            $erreurs[] = 'Ville invalide (ne doit pas contenir de chiffres).';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $donnees['ville'])) {
            $erreurs[] = 'Ville invalide (caractères spéciaux interdits sauf tiret et apostrophe).';
        }
        
        // Couleur (obligatoire) - Validation stricte (pas de chiffres, AUCUN symbole)
        if (!isset($donnees['couleur']) || !Utilitaires::chaineValide($donnees['couleur'], 60)) {
            $erreurs[] = 'Couleur obligatoire.';
        } elseif (preg_match('/\d/', $donnees['couleur'])) {
            $erreurs[] = 'Couleur invalide (ne doit pas contenir de chiffres).';
        } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $donnees['couleur'])) {
            $erreurs[] = 'Couleur invalide (aucun symbole autorisé, lettres et espaces uniquement).';
        }
        
        // Provenance (FACULTATIF) - Validation stricte (pas de chiffres, symboles limités)
        if (!empty($donnees['provenance'])) {
            if (preg_match('/\d/', $donnees['provenance'])) {
                $erreurs[] = 'Provenance invalide (ne doit pas contenir de chiffres).';
            } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $donnees['provenance'])) {
                $erreurs[] = 'Provenance invalide (caractères spéciaux interdits sauf tiret et apostrophe).';
            }
        }
        
        return $erreurs;
    }
    
    /**
     * 🔒 Validation champs ENUM
     */
    private static function validerChampsEnum($donnees, $typeVehicule) {
        $erreurs = [];
        
        // État
        if (!empty($donnees['etat']) && !in_array($donnees['etat'], self::ETATS)) {
            $erreurs[] = 'État invalide.';
        }
        
        // Crit'Air
        if (!empty($donnees['crit_air']) && !in_array($donnees['crit_air'], self::CRIT_AIR)) {
            $erreurs[] = 'Vignette Crit\'Air invalide.';
        }
        
        // Contrôle technique
        if (!empty($donnees['controle_technique']) && !in_array($donnees['controle_technique'], self::CONTROLE_TECHNIQUE)) {
            $erreurs[] = 'Contrôle technique invalide.';
        }
        
        /* 
        // Provenance - DÉSACTIVÉ car champ texte libre demandé
        if (!empty($donnees['provenance']) && !in_array($donnees['provenance'], self::PROVENANCES)) {
            $erreurs[] = 'Provenance invalide.';
        }
        */
        
        // Norme Euro
        if (!empty($donnees['norme_euro']) && !in_array($donnees['norme_euro'], self::NORMES_EURO)) {
            $erreurs[] = 'Norme Euro invalide.';
        }
        
        // Taille coffre (uniquement pour voitures/camions)
        if ($typeVehicule !== 'moto') {
            if (!empty($donnees['taille_coffre']) && !in_array($donnees['taille_coffre'], self::TAILLES_COFFRE)) {
                $erreurs[] = 'Taille de coffre invalide.';
            }
        } else {
            // Moto NE DOIT PAS avoir taille_coffre
            if (!empty($donnees['taille_coffre'])) {
                $erreurs[] = 'Une moto ne peut pas avoir de taille de coffre.';
            }
        }
        
        // Nombre de places (uniquement voitures/camions)
        if ($typeVehicule !== 'moto') {
            if (empty($donnees['nb_places']) || !in_array($donnees['nb_places'], self::NB_PLACES)) {
                $erreurs[] = 'Nombre de places obligatoire.';
            }
        } else {
            // Moto NE DOIT PAS avoir nb_places
            if (!empty($donnees['nb_places'])) {
                $erreurs[] = 'Une moto ne peut pas avoir de nombre de places.';
            }
        }
        
        // Boîte de vitesse
        if ($typeVehicule !== 'moto') {
            if (empty($donnees['boite']) || !in_array($donnees['boite'], self::BOITES)) {
                $erreurs[] = 'Boîte de vitesse obligatoire (Manuelle ou Automatique).';
            }
        } else {
            // Moto NE DOIT PAS avoir boîte
            if (!empty($donnees['boite'])) {
                $erreurs[] = 'Une moto ne peut pas avoir de boîte de vitesse renseignée.';
            }
        }
        
        return $erreurs;
    }
    
    /**
     * 🔒 Validation champs numériques (plages, obligatoires)
     */
    private static function validerChampsNumeriques(&$donnees) {
        $erreurs = [];
        
        // Année (obligatoire, 1900 - année actuelle) - FAILLE #6 : Limite absolue
        $anneeActuelle = min((int)date('Y'), 2025); // Ne jamais dépasser 2025
        if (!isset($donnees['annee']) || !Utilitaires::entierEntre($donnees['annee'], 1900, $anneeActuelle)) {
            $erreurs[] = "Année invalide (1900-{$anneeActuelle}).";
        }
        
        // Prix (obligatoire, 50€ - 10M€) - FAILLE #5 : Validation INF/NAN
        if (!isset($donnees['prix']) || !is_numeric($donnees['prix'])) {
            $erreurs[] = 'Prix invalide (doit être numérique).';
        } else {
            $prix = (float)$donnees['prix'];
            if (!is_finite($prix) || $prix < 50 || $prix > 10000000) {
                $erreurs[] = 'Prix invalide (minimum 50€, maximum 10 millions €).';
            }
        }
        
        // Kilométrage (obligatoire, 10 - 9 999 999 km)
        if (!isset($donnees['km']) || !is_numeric($donnees['km']) || (int)$donnees['km'] < 10 || (int)$donnees['km'] > 9999999) {
            $erreurs[] = 'Kilométrage invalide (minimum 10 km, maximum 9 999 999 km).';
        }
        
        // Puissance (FACULTATIF, 1 - 2000 CV)
        if (isset($donnees['puissance_cv']) && $donnees['puissance_cv'] !== '') {
            if (!is_numeric($donnees['puissance_cv'])) {
                $erreurs[] = 'Puissance invalide (doit être un nombre).';
            } else {
                $cv = (float)$donnees['puissance_cv'];
                if ($cv < 1 || $cv > 2000) {
                    $erreurs[] = 'Puissance invalide (1-2000 CV).';
                } else {
                    $donnees['puissance_cv'] = round($cv, 2);
                }
            }
        }
        
        // Consommation (FACULTATIF, 0.1 - 99.9)
        if (isset($donnees['consommation']) && $donnees['consommation'] !== '') {
            if (!is_numeric($donnees['consommation'])) {
                $erreurs[] = 'Consommation invalide (doit être un nombre).';
            } else {
                $conso = (float)$donnees['consommation'];
                if ($conso < 0.1 || $conso > 99.9) {
                    $erreurs[] = 'Consommation invalide (minimum 0.1 L/100km ou kWh/100km, maximum 99.9).';
                } else {
                    $donnees['consommation'] = round($conso, 2);
                }
            }
        }
        
        // Longueur (FACULTATIF, 1.5 - 20m)
        if (isset($donnees['longueur']) && $donnees['longueur'] !== '') {
            if (!is_numeric($donnees['longueur'])) {
                $erreurs[] = 'Longueur invalide (doit être un nombre).';
            } else {
                $longueur = (float)$donnees['longueur'];
                if ($longueur < 1.5 || $longueur > 20) {
                    $erreurs[] = 'Longueur invalide (1.5m - 20m).';
                } else {
                    $donnees['longueur'] = round($longueur, 2);
                }
            }
        }
        
        // Largeur (FACULTATIF, 1 - 4m)
        if (isset($donnees['largeur']) && $donnees['largeur'] !== '') {
            if (!is_numeric($donnees['largeur'])) {
                $erreurs[] = 'Largeur invalide (doit être un nombre).';
            } else {
                $largeur = (float)$donnees['largeur'];
                if ($largeur < 1 || $largeur > 4) {
                    $erreurs[] = 'Largeur invalide (1m - 4m).';
                } else {
                    $donnees['largeur'] = round($largeur, 2);
                }
            }
        }

        // Hauteur (FACULTATIF, 0.5 - 5m)
        if (isset($donnees['hauteur']) && $donnees['hauteur'] !== '') {
            if (!is_numeric($donnees['hauteur'])) {
                $erreurs[] = 'Hauteur invalide (doit être un nombre).';
            } else {
                $hauteur = (float)$donnees['hauteur'];
                if ($hauteur < 0.5 || $hauteur > 5) {
                    $erreurs[] = 'Hauteur invalide (0.5m - 5m).';
                } else {
                    $donnees['hauteur'] = round($hauteur, 2);
                }
            }
        }
        
        // Nombre de portes (obligatoire pour voitures/camions, 2-6)
        if (isset($donnees['nb_portes']) && $donnees['nb_portes'] !== '' && $donnees['nb_portes'] !== null) {
            if (!is_numeric($donnees['nb_portes'])) {
                $erreurs[] = 'Nombre de portes invalide (doit être numérique).';
            } else {
                $nbPortes = (int)$donnees['nb_portes'];
                if ($nbPortes < 2 || $nbPortes > 6) {
                    $erreurs[] = 'Nombre de portes invalide (2-6).';
                }
            }
        }
        
        // Consommation secondaire (si renseignée, 0.1 - 99.9)
        if (isset($donnees['consommation_secondaire']) && $donnees['consommation_secondaire'] !== '' && $donnees['consommation_secondaire'] !== null) {
            if (!is_numeric($donnees['consommation_secondaire'])) {
                $erreurs[] = 'Consommation secondaire invalide (doit être numérique).';
            } else {
                $consoSec = (float)$donnees['consommation_secondaire'];
                if ($consoSec < 0.1 || $consoSec > 99.9) {
                    $erreurs[] = 'Consommation secondaire invalide (0.1-99.9).';
                } else {
                    $donnees['consommation_secondaire'] = round($consoSec, 2);
                }
            }
        }
        
        // Émission CO2 (si renseignée, 0 - 999)
        if (isset($donnees['emission_co2']) && $donnees['emission_co2'] !== '' && $donnees['emission_co2'] !== null) {
            if (!is_numeric($donnees['emission_co2'])) {
                $erreurs[] = 'Émission CO2 invalide (doit être numérique).';
            } else {
                $co2 = (float)$donnees['emission_co2'];
                if ($co2 < 0 || $co2 > 999) {
                    $erreurs[] = 'Émission CO2 invalide (0-999 g/km).';
                } else {
                    $donnees['emission_co2'] = round($co2, 2);
                }
            }
        }
        
        // Autonomie (si renseignée, 50 - 9999)
        if (isset($donnees['autonomie']) && $donnees['autonomie'] !== '' && $donnees['autonomie'] !== null) {
            if (!is_numeric($donnees['autonomie'])) {
                $erreurs[] = 'Autonomie invalide (doit être numérique).';
            } else {
                $auto = (float)$donnees['autonomie'];
                if ($auto < 50 || $auto > 9999) {
                    $erreurs[] = 'Autonomie invalide (minimum 50 km, maximum 9999 km).';
                } else {
                    $donnees['autonomie'] = round($auto, 2);
                }
            }
        }
        
        return $erreurs;
    }
    
    /**
     * 🔒 Validation cohérence métier (CRITIQUE)
     */
    private static function validerCoherenceMetier($donnees, $typeVehicule, $carburant) {
        $erreurs = [];
        $typeHybride = $donnees['type_hybride'] ?? null;
        
        // 🔴 MOTO : nb_portes INTERDIT
        if ($typeVehicule === 'moto' && !empty($donnees['nb_portes'])) {
            $erreurs[] = 'Une moto ne peut pas avoir de nombre de portes.';
        }
        
        // 🔴 VOITURE/CAMION : nb_portes OBLIGATOIRE
        if ($typeVehicule !== 'moto' && (empty($donnees['nb_portes']) || !is_numeric($donnees['nb_portes']))) {
            $erreurs[] = 'Nombre de portes obligatoire.';
        }
        
        // 🔴 NON-HYBRIDE : type_hybride et consommation_secondaire INTERDITS
        if ($carburant !== 'Hybride') {
            if (!empty($donnees['type_hybride'])) {
                $erreurs[] = 'Le type d\'hybride ne peut être renseigné que pour un carburant Hybride.';
            }
            if (!empty($donnees['consommation_secondaire'])) {
                $erreurs[] = 'La consommation secondaire ne peut être renseignée que pour un carburant Hybride.';
            }
        }
        
        // 🔴 HYBRIDE : type_hybride OBLIGATOIRE
        if ($carburant === 'Hybride') {
            if (empty($donnees['type_hybride'])) {
                $erreurs[] = 'Type d\'hybride obligatoire pour un véhicule hybride.';
            } else if (!in_array($donnees['type_hybride'], self::TYPES_HYBRIDES)) {
                $erreurs[] = 'Type d\'hybride invalide.';
            }
            
            // Consommation secondaire FACULTATIF pour hybride
            if (isset($donnees['consommation_secondaire']) && $donnees['consommation_secondaire'] !== '' && !is_numeric($donnees['consommation_secondaire'])) {
                $erreurs[] = 'Consommation secondaire invalide.';
            }
        }
        
        // 🔴 AUTONOMIE : uniquement pour Électrique ou PHEV
        $typesAvecAutonomie = self::TYPES_AVEC_AUTONOMIE;
        if ($carburant !== 'Électrique' && !in_array($typeHybride, $typesAvecAutonomie)) {
            // Non-électrique/PHEV ne DOIT PAS avoir autonomie
            if (!empty($donnees['autonomie'])) {
                $erreurs[] = 'L\'autonomie ne peut être renseignée que pour les véhicules électriques ou hybrides rechargeables.';
            }
        }
        
        // 🔴 ÉLECTRIQUE/PHEV : autonomie FACULTATIF
        // if ($carburant === 'Électrique' || in_array($typeHybride, $typesAvecAutonomie)) {
        //     if (empty($donnees['autonomie']) || (int)$donnees['autonomie'] <= 0) {
        //         $erreurs[] = 'Autonomie obligatoire pour les véhicules électriques ou hybrides rechargeables.';
        //     }
        // }
        
        return $erreurs;
    }
    
    /**
     * 🔒 Validation images (8 contrôles de sécurité)
     */
    private static function validerImages($fichiers, $donnees, $typeOperation) {
        $erreurs = [];
        
        if ($typeOperation === 'ajout') {
            // Mode AJOUT : fichiers obligatoires
            return self::validerImagesAjout($fichiers);
        } else {
            // Mode MODIFICATION : fichiers optionnels
            return self::validerImagesModification($fichiers, $donnees);
        }
    }
    
    private static function validerImagesAjout($fichiers) {
        $erreurs = [];
        $fichiersImages = $fichiers['images'] ?? null;
        
        if (!$fichiersImages || !isset($fichiersImages['name']) || !is_array($fichiersImages['name'])) {
            $erreurs[] = 'Il vous manque 3 images ! Au minimum 3 photos sont requises pour publier une annonce.';
            return $erreurs;
        }
        
        $nombreImages = 0;
        $typesAutorise = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $tailleMaxParImage = 5 * 1024 * 1024; // 5 Mo
        
        for ($i = 0; $i < count($fichiersImages['name']); $i++) {
            if (empty($fichiersImages['name'][$i])) continue;
            
            // 1️⃣ Validation nom fichier (injection)
            $nomFichier = basename($fichiersImages['name'][$i]);
            if (preg_match('/[^a-zA-Z0-9_\-\.]/', $nomFichier)) {
                $erreurs[] = "Image {$i} : nom de fichier invalide (caractères spéciaux interdits).";
            }
            if (strlen($nomFichier) > 100) {
                $erreurs[] = "Image {$i} : nom de fichier trop long (maximum 100 caractères).";
            }
            
            // 2️⃣ Extension
            $extension = strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $erreurs[] = "Image {$i} : extension invalide (seuls .jpg, .png, .webp acceptés).";
            }
            
            $nombreImages++;
            
            // 3️⃣ Type MIME
            if (isset($fichiersImages['tmp_name'][$i]) && file_exists($fichiersImages['tmp_name'][$i])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $fichiersImages['tmp_name'][$i]);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $typesAutorise)) {
                    $erreurs[] = "Image {$i} : type non autorisé (seuls JPG, PNG, WEBP acceptés).";
                }
                
                // 4️⃣ Taille fichier
                if ($fichiersImages['size'][$i] > $tailleMaxParImage) {
                    $erreurs[] = "Image {$i} : taille trop grande (maximum 5 Mo).";
                }
                
                // 5️⃣ Dimensions
                $dimensions = @getimagesize($fichiersImages['tmp_name'][$i]);
                if ($dimensions === false) {
                    $erreurs[] = "Image {$i} : fichier image corrompu ou invalide.";
                } else {
                    if ($dimensions[0] < 200 || $dimensions[1] < 200) {
                        $erreurs[] = "Image {$i} : dimensions trop petites (minimum 200×200 px).";
                    }
                    if ($dimensions[0] > 10000 || $dimensions[1] > 10000) {
                        $erreurs[] = "Image {$i} : dimensions trop grandes (maximum 10000×10000 px).";
                    }
                }
            }
        }
        
        // 6️⃣ Nombre d'images
        if ($nombreImages < 3) {
            $nombreManquant = 3 - $nombreImages;
            $imageTexte = $nombreManquant > 1 ? 'images' : 'image';
            $erreurs[] = "Il vous manque {$nombreManquant} {$imageTexte} ! Vous devez télécharger au minimum 3 photos de votre véhicule (actuellement : {$nombreImages}/3).";
        }
        if ($nombreImages > 10) {
            $erreurs[] = 'Vous ne pouvez télécharger que 10 images maximum.';
        }
        
        return $erreurs;
    }
    
    private static function validerImagesModification($fichiers, $donnees) {
        $erreurs = [];
        
        // Valider images_existantes (JSON)
        $imagesAConserver = [];
        if (isset($donnees['images_existantes'])) {
            // 🔒 SÉCURITÉ : Vérifier que c'est une chaîne avant json_decode
            if (!is_string($donnees['images_existantes'])) {
                $erreurs[] = 'Format images_existantes invalide.';
            } else {
                $decoded = json_decode($donnees['images_existantes'], true);
                if (!is_array($decoded)) {
                    $erreurs[] = 'Format JSON images_existantes invalide.';
                } else {
                    // Limiter à 10 images max
                    $imagesAConserver = array_slice($decoded, 0, 10);
                    foreach ($imagesAConserver as $img) {
                        if (!is_string($img) || strlen($img) > 500) {
                            $erreurs[] = 'Format d\'images existantes invalide.';
                            break;
                        }
                    }
                }
            }
        }
        
        // FAILLE #9 : Valider nouvelles images sans exiger minimum 3
        $nombreNouvellesImages = 0;
        $nouvellesImages = $fichiers['images'] ?? null;
        
        if ($nouvellesImages && isset($nouvellesImages['name']) && is_array($nouvellesImages['name'])) {
            // Validation individuelle de chaque image (PAS le minimum 3)
            $erreursNouvellesImages = self::validerImagesIndividuelles($fichiers);
            $erreurs = array_merge($erreurs, $erreursNouvellesImages);
            
            // Compter images valides
            for ($i = 0; $i < count($nouvellesImages['name']); $i++) {
                if (!empty($nouvellesImages['name'][$i])) {
                    $nombreNouvellesImages++;
                }
            }
        }
        
        // Total images (existantes + nouvelles) : min 3, max 10
        $nombreTotal = count($imagesAConserver) + $nombreNouvellesImages;
        if ($nombreTotal < 3) {
            $nombreManquant = 3 - $nombreTotal;
            $erreurs[] = "Il vous manque {$nombreManquant} image(s) ! Minimum 3 photos (actuellement : {$nombreTotal}/3).";
        }
        if ($nombreTotal > 10) {
            $erreurs[] = 'Vous ne pouvez avoir que 10 images maximum.';
        }
        
        return $erreurs;
    }
    
    /**
     * 🔒 Validation images individuelles (sans minimum 3) - FAILLE #9
     */
    private static function validerImagesIndividuelles($fichiers) {
        $erreurs = [];
        $fichiersImages = $fichiers['images'] ?? null;
        
        if (!$fichiersImages || !isset($fichiersImages['name']) || !is_array($fichiersImages['name'])) {
            return $erreurs;
        }
        
        $typesAutorise = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $tailleMaxParImage = 5 * 1024 * 1024; // 5 Mo
        
        for ($i = 0; $i < count($fichiersImages['name']); $i++) {
            if (empty($fichiersImages['name'][$i])) continue;
            
            // 1️⃣ Validation nom fichier
            $nomFichier = basename($fichiersImages['name'][$i]);
            if (preg_match('/[^a-zA-Z0-9_\-\.]/', $nomFichier)) {
                $erreurs[] = "Image {$i} : nom de fichier invalide.";
            }
            if (strlen($nomFichier) > 100) {
                $erreurs[] = "Image {$i} : nom trop long.";
            }
            
            // 2️⃣ Extension
            $extension = strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $erreurs[] = "Image {$i} : extension invalide.";
            }
            
            // 3️⃣ Type MIME
            if (isset($fichiersImages['tmp_name'][$i]) && file_exists($fichiersImages['tmp_name'][$i])) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $fichiersImages['tmp_name'][$i]);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $typesAutorise)) {
                    $erreurs[] = "Image {$i} : type non autorisé.";
                }
                
                // 4️⃣ Taille
                if ($fichiersImages['size'][$i] > $tailleMaxParImage) {
                    $erreurs[] = "Image {$i} : taille trop grande.";
                }
                
                // 5️⃣ Dimensions
                $dimensions = @getimagesize($fichiersImages['tmp_name'][$i]);
                if ($dimensions === false) {
                    $erreurs[] = "Image {$i} : fichier corrompu.";
                } else {
                    if ($dimensions[0] < 200 || $dimensions[1] < 200) {
                        $erreurs[] = "Image {$i} : dimensions trop petites.";
                    }
                    if ($dimensions[0] > 10000 || $dimensions[1] > 10000) {
                        $erreurs[] = "Image {$i} : dimensions trop grandes.";
                    }
                }
            }
        }
        
        return $erreurs;
    }
    
    /**
     * 🔒 Nettoyer les champs interdits selon type véhicule et carburant
     */
    private static function nettoyerChamps($donnees, $typeVehicule, $carburant) {
        // Moto : supprimer champs interdits
        if ($typeVehicule === 'moto') {
            $donnees['nb_portes'] = null;
            $donnees['taille_coffre'] = null;
            $donnees['boite'] = null;
            $donnees['nb_places'] = null;
        }
        
        // Non-hybride : supprimer champs hybrides
        if ($carburant !== 'Hybride') {
            $donnees['type_hybride'] = null;
            $donnees['consommation_secondaire'] = null;
        }
        
        // Non-électrique/PHEV : supprimer autonomie
        $typeHybride = $donnees['type_hybride'] ?? null;
        $typesAvecAutonomie = self::TYPES_AVEC_AUTONOMIE;
        if ($carburant !== 'Électrique' && !in_array($typeHybride, $typesAvecAutonomie)) {
            $donnees['autonomie'] = null;
        }
        
        return $donnees;
    }
}
