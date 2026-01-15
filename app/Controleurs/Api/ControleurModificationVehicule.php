<?php

/**
 * 🔒 CONTRÔLEUR MODIFICATION - VERSION SÉCURISÉE DÉFINITIVE
 * 
 * Utilise ValidateurVehicule pour toutes les validations
 * 100% sécurisé contre : injection SQL, XSS, CSRF, upload malveillant, incohérences métier
 * + Vérification propriétaire/admin
 */
class ControleurModificationVehicule {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }
    
    /**
     * Normalise le nom d'une ville pour comparaison
     * Uniformise les tirets, espaces et la casse
     * @param string|null $ville Le nom de la ville
     * @return string|null Le nom normalisé
     */
    private function normaliserVille($ville) {
        if ($ville === null || $ville === '') return null;
        
        return strtolower(
            preg_replace('/\s+/', ' ', 
                str_replace('-', ' ', trim($ville))
            )
        );
    }

    public function traiterRequete($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->gererPost($id);
        } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->gererGet($id);
        } else {
            Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
        }
    }
    
    /**
     * GET : Récupérer données véhicule (pour affichage formulaire)
     */
    private function gererGet($id) {
        // 1️⃣ Authentification
        if (!GestionnaireSession::estConnecte()) {
            Utilitaires::envoyerJSON(['erreur' => 'Authentification requise'], 401);
        }
        
        $userId = (int)$_SESSION['user']['id'];
        $estAdmin = $_SESSION['user']['est_administrateur'] ?? false;
        
        // 2️⃣ Récupérer véhicule
        $vehicule = $this->modele->obtenirParId($id);
        
        if (!$vehicule) {
            Utilitaires::envoyerJSON(['erreur' => 'Véhicule introuvable'], 404);
        }
        
        // 3️⃣ Vérifier autorisation (propriétaire ou admin)
        $estProprietaire = ((int)$vehicule['user_id'] === $userId);
        
        if (!$estProprietaire && !$estAdmin) {
            Utilitaires::envoyerJSON(['erreur' => 'Vous n\'êtes pas autorisé à modifier ce véhicule'], 403);
        }
        
        // 4️⃣ Bloquer modification si en attente de vérification (sauf admin)
        if ($vehicule['status'] === 'en_attente' && !$estAdmin) {
            Utilitaires::envoyerJSON(['erreur' => 'Cette annonce est en cours de vérification et ne peut pas être modifiée.'], 403);
        }
        
        Utilitaires::envoyerJSON(['vehicule' => $vehicule], 200);
    }
    
    /**
     * POST : Modifier véhicule
     */
    private function gererPost($id) {
        // 1️⃣ Authentification
        if (!GestionnaireSession::estConnecte()) {
            Utilitaires::envoyerJSON(['erreur' => 'Authentification requise'], 401);
        }
        
        // 2️⃣ Validation CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (is_array($csrfToken)) $csrfToken = ''; // Protection type
        
        if (!GestionnaireSession::validerTokenCSRF($csrfToken)) {
            Utilitaires::envoyerJSON(['erreur' => 'Token CSRF invalide. Veuillez recharger la page.'], 403);
        }
        
        $userId = (int)$_SESSION['user']['id'];
        $estAdmin = $_SESSION['user']['est_administrateur'] ?? false;
        
        // 3️⃣ Vérifier propriété
        $vehicule = $this->modele->obtenirParId($id);
        
        if (!$vehicule) {
            Utilitaires::envoyerJSON(['erreur' => 'Véhicule introuvable'], 404);
        }
        
        $estProprietaire = ((int)$vehicule['user_id'] === $userId);
        
        if (!$estProprietaire && !$estAdmin) {
            Utilitaires::envoyerJSON(['erreur' => 'Vous n\'êtes pas autorisé à modifier ce véhicule'], 403);
        }
        
        // Bloquer modification si en attente de vérification (sauf admin)
        if ($vehicule['status'] === 'en_attente' && !$estAdmin) {
            Utilitaires::envoyerJSON(['erreur' => 'Cette annonce est en cours de vérification et ne peut pas être modifiée.'], 403);
        }
        
        // 4️⃣ 🔒 VALIDATION CENTRALISÉE (100% SÉCURISÉ)
        $resultatValidation = ValidateurVehicule::valider($_POST, $_FILES, 'modification');
        
        if (!$resultatValidation['valide']) {
            Utilitaires::envoyerJSON(['erreur' => implode(' ', $resultatValidation['erreurs'])], 400);
        }
        
        // 5️⃣ Préparer données nettoyées
        $donnees = $resultatValidation['donnees_nettoyees'];
        
        // 6️⃣ Mettre à jour BDD via modèle existant
        try {
            $fichiersImages = $_FILES['images'] ?? null;
            // Décodage du JSON des images existantes
            $imagesExistantes = json_decode($_POST['images_existantes'] ?? '[]', true);
            if (!is_array($imagesExistantes)) {
                $imagesExistantes = [];
            }
            
            $resultatModification = $this->modele->modifier($id, $donnees, $fichiersImages, $imagesExistantes);
            
            // 7️⃣ Recalculer le score IA après modification
            try {
                require_once __DIR__ . '/../../Modeles/ModeleScoreIA.php';
                $modeleScoreIA = new ModeleScoreIA();
                $modeleScoreIA->calculerEtSauvegarder($id, $donnees);
            } catch (Exception $e) {
                // Ignorer l'erreur du score IA - ne pas bloquer la modification
                error_log('Erreur recalcul score IA: ' . $e->getMessage());
            }
            
            // Log modification annonce - détecter les vrais changements
            try {
                $modeleAdmin = new ModeleAdmin();
                
                // Mapping des champs pour comparer anciennes/nouvelles valeurs
                $mappingChamps = [
                    'type_vehicule' => 'type_vehicule',
                    'marque' => 'marque',
                    'modele' => 'modele',
                    'annee' => 'annee',
                    'prix' => 'prix',
                    'km' => 'km',
                    'code_postal' => 'code_postal',
                    'ville' => 'ville',
                    'carburant' => 'carburant',
                    'boite' => 'boite',
                    'etat' => 'etat',
                    'couleur' => 'couleur',
                    'crit_air' => 'crit_air',
                    'nb_portes' => 'nb_portes',
                    'nb_places' => 'nb_places',
                    'taille_coffre' => 'taille_coffre',
                    'longueur' => 'longueur',
                    'largeur' => 'largeur',
                    'hauteur' => 'hauteur',
                    'puissance_cv' => 'puissance_cv',
                    'norme_euro' => 'norme_euro',
                    'type_hybride' => 'type_hybride',
                    'consommation' => 'consommation',
                    'consommation_secondaire' => 'consommation_secondaire',
                    'emission_co2' => 'emission_co2',
                    'autonomie' => 'autonomie',
                    'controle_technique' => 'controle_technique',
                    'provenance' => 'provenance',
                    'description' => 'description'
                ];
                
                // Identifier les champs réellement modifiés
                $champsModifies = [];
                foreach ($mappingChamps as $champFormulaire => $champBDD) {
                    if (isset($donnees[$champFormulaire])) {
                        $nouvelleValeur = $donnees[$champFormulaire];
                        $ancienneValeur = $vehicule[$champBDD] ?? null;
                        
                        // Normaliser pour comparaison (strings, nulls, nombres)
                        $nouvelleValeurNorm = is_null($nouvelleValeur) || $nouvelleValeur === '' ? null : (string)$nouvelleValeur;
                        $ancienneValeurNorm = is_null($ancienneValeur) || $ancienneValeur === '' ? null : (string)$ancienneValeur;
                        
                        // Normalisation spéciale pour les villes (tirets, espaces, casse)
                        if ($champFormulaire === 'ville') {
                            $nouvelleValeurNorm = $this->normaliserVille($nouvelleValeurNorm);
                            $ancienneValeurNorm = $this->normaliserVille($ancienneValeurNorm);
                        }
                        
                        if ($nouvelleValeurNorm !== $ancienneValeurNorm) {
                            $champsModifies[] = $champFormulaire;
                        }
                    }
                }
                
                // Vérifier si les images ont été modifiées
                if (!empty($fichiersImages['name'][0]) || !empty($imagesExistantes)) {
                    $champsModifies[] = 'images';
                }
                
                // Vérifier si la couverture a changé
                if (isset($_POST['couverture_type']) && isset($_POST['couverture_index'])) {
                    $couvertureType = $_POST['couverture_type'];
                    $couvertureIndex = (int)$_POST['couverture_index'];
                    // Si ce n'est plus l'image existante index 0 (la première), c'est une modification
                    if ($couvertureType !== 'existante' || $couvertureIndex !== 0) {
                        $champsModifies[] = 'photo_couverture';
                    }
                }
                
                // Ne logger que si des modifications réelles ont eu lieu
                if (!empty($champsModifies)) {
                    $modeleAdmin->ajouterLog('annonce_modification', 'Annonce modifiée', [
                        'marque' => $donnees['marque'] ?? $vehicule['marque'] ?? '',
                        'modele' => $donnees['modele'] ?? $vehicule['modele'] ?? '',
                        'annee' => $donnees['annee'] ?? $vehicule['annee'] ?? '',
                        'prix' => $donnees['prix'] ?? $vehicule['prix'] ?? '',
                        'modifications' => $champsModifies
                    ], $userId, $id, null);
                }
            } catch (Exception $logError) {
                error_log('Erreur log modification annonce: ' . $logError->getMessage());
            }
            
            Utilitaires::envoyerJSON(['ok' => true, 'vehicule' => $resultatModification], 200);
        } catch (Throwable $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }
}

// 🔒 VALIDATION ID : Cast et vérification plage
$idInput = $_GET['id'] ?? 0;
if (is_array($idInput)) $idInput = 0; // Protection contre injection tableau id[]=...
$id = (int)$idInput;

if ($id <= 0 || $id > 2147483647) {
    Utilitaires::envoyerJSON(['erreur' => 'ID véhicule invalide'], 400);
}

$controleur = new ControleurModificationVehicule();
$controleur->traiterRequete($id);
