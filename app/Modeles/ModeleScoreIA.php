<?php
/**
 * ModeleScoreIA - Calcule un score IA pour évaluer si une annonce est une bonne affaire
 * Score de 0 (très mauvaise affaire) à 100 (excellente affaire)
 */
class ModeleScoreIA {
    private $pdo;
    private $apiKey;

    public function __construct() {
        $config = require __DIR__ . '/../../config.php';
        $this->apiKey = $config['openai_api_key'] ?? '';
        
        $dsn = "mysql:host={$config['db_host']}";
        if (isset($config['db_port'])) {
            $dsn .= ";port={$config['db_port']}";
        }
        $dsn .= ";dbname={$config['db_name']};charset=utf8mb4";
        
        $this->pdo = new PDO(
            $dsn,
            $config['db_user'],
            $config['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * Calcule le score IA pour un véhicule
     * @param array $vehicule Données du véhicule
     * @return array ['score' => int, 'label' => string, 'conseil' => string]
     */
    public function calculerScore($vehicule) {
        if (empty($this->apiKey) || $this->apiKey === 'VOTRE_CLE_API_OPENAI_ICI') {
            return $this->calculerScoreAlgorithmique($vehicule);
        }

        try {
            return $this->calculerScoreIA($vehicule);
        } catch (Exception $e) {
            error_log('Score IA - Erreur API: ' . $e->getMessage());
            return $this->calculerScoreAlgorithmique($vehicule);
        }
    }

    /**
     * Calcul du score via l'API OpenAI - Version simplifiée et fiable
     */
    private function calculerScoreIA($vehicule) {
        $prix = (int)($vehicule['prix'] ?? 0);
        $marque = trim($vehicule['marque'] ?? '');
        $modele = trim($vehicule['modele'] ?? '');
        $annee = (int)($vehicule['annee'] ?? date('Y'));
        $km = (int)($vehicule['km'] ?? 0);
        $etat = $vehicule['etat'] ?? 'bon';
        $carburant = $vehicule['carburant'] ?? '';
        $description = $vehicule['description'] ?? '';
        
        // Créer un hash déterministe basé sur les caractéristiques du véhicule
        // Cela permet d'avoir un seed cohérent pour le même véhicule
        $seedData = strtolower(trim($marque)) . '|' . strtolower(trim($modele)) . '|' . $annee . '|' . round($km, -3) . '|' . $etat;
        $seed = abs(crc32($seedData));
        
        // Prompt simplifié et strict - demande UNIQUEMENT valeur_estimee
        $prompt = "TÂCHE: Estimer la valeur marché d'un véhicule d'occasion en France.\n\n" .
            "VÉHICULE À ÉVALUER:\n" .
            "- Marque: {$marque}\n" .
            "- Modèle: {$modele}\n" .
            "- Année: {$annee}\n" .
            "- Kilométrage: " . number_format($km, 0, '', ' ') . " km\n" .
            "- État: {$etat}\n" .
            "- Carburant: {$carburant}\n\n" .
            "RÈGLES STRICTES:\n" .
            "1. VÉRIFICATION OBLIGATOIRE: Le modèle \"" . $modele . "\" de la marque \"" . $marque . "\" DOIT exister dans la réalité.\n" .
            "2. Si ce modèle N'EXISTE PAS ou est inventé → retourne valeur_estimee: null\n" .
            "3. Si ce modèle EXISTE → calcule la valeur selon la cote Argus/La Centrale pour " . date('Y') . ".\n" .
            "4. Base-toi sur des données RÉELLES du marché français de l'occasion.\n" .
            "5. Prends en compte: année, kilométrage, état général, type de carburant.\n\n" .
            "RÉPONSE OBLIGATOIRE (JSON uniquement, sans markdown):\n" .
            "{\"valeur_estimee\": NOMBRE_OU_NULL, \"raison\": \"explication_courte\"}";

        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un expert automobile certifié, spécialisé dans l\'évaluation de véhicules d\'occasion en France. Tu connais parfaitement tous les modèles de véhicules existants et leurs cotes Argus. Tu NE dois JAMAIS estimer un véhicule dont le modèle n\'existe pas. Réponds UNIQUEMENT en JSON valide.'
                ],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0,
            'max_tokens' => 150,
            'seed' => $seed
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 15
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Erreur API HTTP: {$httpCode}");
        }

        $result = json_decode($response, true);
        $content = trim($result['choices'][0]['message']['content'] ?? '');
        
        // Nettoyer le JSON
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);
        
        $jsonData = json_decode($content, true);
        
        if (!$jsonData || !array_key_exists('valeur_estimee', $jsonData)) {
            throw new Exception('Réponse JSON invalide');
        }
        
        $valeurEstimee = $jsonData['valeur_estimee'];
        $raison = $jsonData['raison'] ?? '';
        
        // Véhicule invalide
        if ($valeurEstimee === null) {
            return [
                'score' => null,
                'label' => 'Impossible à évaluer',
                'conseil' => $raison ?: 'Ce véhicule ne peut pas être évalué.',
                'alerte' => null,
                'raisonnement' => 'Véhicule non reconnu'
            ];
        }
        
        // CALCUL DU SCORE basé sur le ratio prix/valeur
        $valeurEstimee = (float)$valeurEstimee;
        
        if ($valeurEstimee <= 0) {
            throw new Exception('Valeur estimée invalide');
        }
        
        $ratio = ($prix / $valeurEstimee) * 100;
        
        // Barème de score selon le ratio
        // ratio < 60% = excellente affaire (score 80-100)
        // ratio 60-80% = bonne affaire (score 60-79)
        // ratio 80-100% = prix correct (score 45-59)
        // ratio 100-120% = légèrement cher (score 30-44)
        // ratio 120-150% = cher (score 15-29)
        // ratio > 150% = très cher (score 0-14)
        
        if ($ratio < 60) {
            // Excellente affaire : interpoler entre 80 et 100
            $score = 100 - (($ratio / 60) * 20);
            $score = max(80, min(100, round($score)));
        } elseif ($ratio < 80) {
            // Bonne affaire : interpoler entre 60 et 79
            $position = ($ratio - 60) / 20;
            $score = 79 - ($position * 19);
            $score = max(60, min(79, round($score)));
        } elseif ($ratio < 100) {
            // Prix correct : interpoler entre 45 et 59
            $position = ($ratio - 80) / 20;
            $score = 59 - ($position * 14);
            $score = max(45, min(59, round($score)));
        } elseif ($ratio < 120) {
            // Légèrement cher : interpoler entre 30 et 44
            $position = ($ratio - 100) / 20;
            $score = 44 - ($position * 14);
            $score = max(30, min(44, round($score)));
        } elseif ($ratio < 150) {
            // Cher : interpoler entre 15 et 29
            $position = ($ratio - 120) / 30;
            $score = 29 - ($position * 14);
            $score = max(15, min(29, round($score)));
        } else {
            // Très cher : interpoler entre 0 et 14
            $position = min(1, ($ratio - 150) / 50);
            $score = 14 - ($position * 14);
            $score = max(0, min(14, round($score)));
        }
        
        // Alerte si prix anormalement bas (possible arnaque)
        $alerte = null;
        if ($ratio < 40) {
            $alerte = "⚠️ Prix très bas par rapport au marché. Vérifiez l'authenticité de l'annonce et l'état réel du véhicule.";
        }
        
        // Conseil personnalisé
        $conseil = $this->genererConseil($score, $ratio, $prix, $valeurEstimee);
        
        $raisonnement = sprintf(
            "Valeur marché estimée: %s €. Prix demandé: %s € (%.0f%% de la valeur). %s",
            number_format($valeurEstimee, 0, ',', ' '),
            number_format($prix, 0, ',', ' '),
            $ratio,
            $raison
        );
        
        error_log("Score IA: {$marque} {$modele} - Prix: {$prix}€, Valeur: {$valeurEstimee}€, Ratio: {$ratio}%, Score: {$score}");
        
        return [
            'score' => (int)$score,
            'label' => $this->getLabel($score),
            'conseil' => $conseil,
            'alerte' => $alerte,
            'raisonnement' => $raisonnement
        ];
    }
    
    /**
     * Génère un conseil personnalisé selon le score
     */
    private function genererConseil($score, $ratio, $prix, $valeurEstimee) {
        $diff = $prix - $valeurEstimee;
        $diffAbs = abs($diff);
        $diffFormate = number_format($diffAbs, 0, ',', ' ');
        
        if ($score >= 80) {
            return "Excellente affaire ! Le prix est {$diffFormate} € sous la valeur du marché.";
        } elseif ($score >= 60) {
            return "Bonne affaire, le véhicule est proposé en dessous de sa valeur marché.";
        } elseif ($score >= 45) {
            return "Prix conforme au marché. Vous pouvez négocier légèrement.";
        } elseif ($score >= 30) {
            return "Prix un peu élevé, environ {$diffFormate} € au-dessus du marché. Négociez.";
        } elseif ($score >= 15) {
            return "Prix élevé, {$diffFormate} € au-dessus de la valeur marché. Négociation fortement conseillée.";
        } else {
            return "Prix très élevé par rapport au marché. Comparez avec d'autres offres avant d'acheter.";
        }
    }

    /**
     * Calcul algorithmique du score (fallback quand l'API OpenAI n'est pas disponible)
     * Note: Ce calcul ne vérifie PAS la validité marque/modèle - seule l'IA le fait
     */
    private function calculerScoreAlgorithmique($vehicule) {
        $score = 50; // Score de base
        
        $annee = (int)($vehicule['annee'] ?? date('Y'));
        $km = (int)($vehicule['km'] ?? 0);
        $prix = (float)($vehicule['prix'] ?? 0);
        $etat = $vehicule['etat'] ?? 'bon';
        $carburant = strtolower($vehicule['carburant'] ?? '');
        $age = date('Y') - $annee;
        
        // Facteur km/année (moyenne 15000 km/an)
        $kmAttendu = $age * 15000;
        if ($km > 0 && $kmAttendu > 0) {
            $ratioKm = $km / $kmAttendu;
            if ($ratioKm < 0.7) $score += 15; // Faible km
            elseif ($ratioKm < 0.9) $score += 8;
            elseif ($ratioKm > 1.3) $score -= 10; // Km élevé
            elseif ($ratioKm > 1.5) $score -= 20;
        }
        
        // Facteur état
        switch ($etat) {
            case 'neuf': $score += 15; break;
            case 'bon': $score += 5; break;
            case 'moyen': $score -= 5; break;
            case 'mauvais': $score -= 15; break;
        }
        
        // Facteur carburant (tendances actuelles)
        if (strpos($carburant, 'electrique') !== false || strpos($carburant, 'électrique') !== false) {
            $score += 10;
        } elseif (strpos($carburant, 'hybride') !== false) {
            $score += 8;
        } elseif (strpos($carburant, 'diesel') !== false && $annee < 2015) {
            $score -= 10; // Diesel ancien moins recherché
        }
        
        // Facteur âge
        if ($age <= 2) $score += 10;
        elseif ($age <= 5) $score += 5;
        elseif ($age > 10) $score -= 5;
        elseif ($age > 15) $score -= 10;
        
        // Limiter entre 0 et 100
        $score = max(0, min(100, $score));
        
        return [
            'score' => $score,
            'label' => $this->getLabel($score),
            'conseil' => $this->getConseilDefaut($score),
            'alerte' => null,
            'raisonnement' => 'Calcul algorithmique basé sur km/année, état, carburant'
        ];
    }

    /**
     * Obtenir le label correspondant au score
     */
    private function getLabel($score) {
        if ($score >= 81) return 'Excellente affaire';
        if ($score >= 61) return 'Bonne affaire';
        if ($score >= 41) return 'Affaire correcte';
        if ($score >= 21) return 'À négocier';
        return 'Prix élevé';
    }

    /**
     * Obtenir un conseil par défaut selon le score
     */
    private function getConseilDefaut($score) {
        if ($score >= 81) return 'Prix très attractif pour ce véhicule, n\'hésitez pas !';
        if ($score >= 61) return 'Bon rapport qualité-prix, une opportunité à saisir.';
        if ($score >= 41) return 'Prix conforme au marché, vérifiez l\'historique.';
        if ($score >= 21) return 'Prix un peu élevé, négociation recommandée.';
        return 'Prix supérieur au marché, comparez avec d\'autres offres.';
    }

    /**
     * Enregistrer le score en base de données
     */
    public function sauvegarderScore($vehiculeId, $score) {
        $stmt = $this->pdo->prepare("UPDATE vehicles SET score_ia = :score WHERE id = :id");
        return $stmt->execute([
            ':score' => $score,
            ':id' => $vehiculeId
        ]);
    }

    /**
     * Récupérer le score d'un véhicule
     */
    public function getScore($vehiculeId) {
        $stmt = $this->pdo->prepare("
            SELECT score_ia, prix, marque, modele, annee 
            FROM vehicles 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $vehiculeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['score_ia'] !== null) {
            $score = (int)$result['score_ia'];
            
            // Générer l'alerte si le score est très élevé (>= 90)
            // Cela signifie un prix < 30% de la valeur
            $alerte = null;
            if ($score >= 90) {
                $alerte = "Prix anormalement bas ! Vérifiez l'état réel du véhicule, l'historique et l'authenticité de l'annonce avant tout achat.";
            }
            
            return [
                'score' => $score,
                'label' => $this->getLabel($score),
                'conseil' => $this->getConseilDefaut($score),
                'alerte' => $alerte
            ];
        }
        
        return null;
    }

    /**
     * Calculer et sauvegarder le score pour un véhicule
     */
    public function calculerEtSauvegarder($vehiculeId, $vehicule = null) {
        // Si pas de données véhicule, les récupérer
        if (!$vehicule) {
            $stmt = $this->pdo->prepare("SELECT * FROM vehicles WHERE id = :id");
            $stmt->execute([':id' => $vehiculeId]);
            $vehicule = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$vehicule) {
            throw new Exception('Véhicule non trouvé');
        }
        
        $resultat = $this->calculerScore($vehicule);
        $this->sauvegarderScore($vehiculeId, $resultat['score']);
        
        return $resultat;
    }
}
