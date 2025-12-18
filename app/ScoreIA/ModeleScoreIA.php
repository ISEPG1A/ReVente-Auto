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
            // Fallback : calcul algorithmique si pas d'API
            return $this->calculerScoreAlgorithmique($vehicule);
        }

        try {
            return $this->calculerScoreIA($vehicule);
        } catch (Exception $e) {
            // En cas d'erreur API, utiliser le calcul algorithmique
            return $this->calculerScoreAlgorithmique($vehicule);
        }
    }

    /**
     * Calcul du score via l'API OpenAI
     */
    private function calculerScoreIA($vehicule) {
        $prix = $vehicule['prix'] ?? 0;
        $description = $vehicule['description'] ?? 'Non renseignée';
        $marque = $vehicule['marque'] ?? '';
        $modele = $vehicule['modele'] ?? '';
        
        $prompt = "Tu es expert automobile français. Évalue ce véhicule avec un score de 0 à 100.\n\n" .
            "⚠️ VÉRIFICATION PRÉALABLE ⚠️\n" .
            "Vérifie que la combinaison marque+modèle est RÉELLE :\n" .
            "- La marque doit être un VRAI constructeur automobile (Peugeot, Renault, BMW, Toyota, etc.)\n" .
            "- Le modèle doit exister ou avoir existé pour cette marque\n" .
            "- Les variantes/finitions sont ACCEPTÉES (GT Line, RS, AMG, Type R, Sport, etc.)\n\n" .
            "REFUSER (score: null) UNIQUEMENT si :\n" .
            "- Marque complètement inventée/fictive (ex: 'Voituro', 'CarMaster')\n" .
            "- Modèle d'une AUTRE marque (ex: BMW Clio, Peugeot Golf)\n" .
            "- Charabia évident (ex: 'azertyuiop qsdfgh')\n" .
            "- Entrées de test (ex: 'Test Test', 'AAA BBB')\n\n" .
            "ACCEPTER les modèles avec finitions/versions :\n" .
            "- '208 GT Line' = Peugeot 208 finition GT Line ✓\n" .
            "- 'Golf GTI' = VW Golf version GTI ✓\n" .
            "- 'Classe A 180' = Mercedes Classe A moteur 180 ✓\n" .
            "- 'Série 3 320d' = BMW Série 3 moteur 320d ✓\n\n" .
            "VÉHICULE À ÉVALUER :\n" .
            "Marque: {$marque}\n" .
            "Modèle: {$modele}\n" .
            "Année: " . ($vehicule['annee'] ?? '') . " • " .
            "Km: " . ($vehicule['km'] ?? '') . " • " .
            "État: " . ($vehicule['etat'] ?? '') . " • " .
            "Carburant: " . ($vehicule['carburant'] ?? '') . "\n" .
            "Prix annoncé: {$prix}€\n\n" .
            "DESCRIPTION DE L'ANNONCE :\n" .
            "\"{$description}\"\n\n" .
            "SI LE VÉHICULE EST VALIDE, évalue-le :\n\n" .
            "MÉTHODE D'ÉVALUATION :\n" .
            "1. VALEUR DE BASE : Estime la valeur marché de ce modèle en BON ÉTAT avec kilométrage MOYEN pour son âge\n\n" .
            "2. AJUSTEMENTS selon les critères :\n" .
            "   KILOMÉTRAGE :\n" .
            "   • Très faible (<10000 km/an) → +5% à +15%\n" .
            "   • Moyen (10000-15000 km/an) → 0%\n" .
            "   • Élevé (>15000 km/an) → -5% à -30%\n\n" .
            "   ÉTAT :\n" .
            "   • Neuf/Excellent → +5% à +10%\n" .
            "   • Bon → 0%\n" .
            "   • Moyen → -10% à -20%\n" .
            "   • Mauvais → -30% à -50%\n\n" .
            "3. RATIO = (Prix annoncé ÷ Valeur ajustée) × 100\n\n" .
            "4. SCORE :\n" .
            "   • ratio < 50% → score 80-100\n" .
            "   • ratio 50-80% → score 60-79\n" .
            "   • ratio 80-110% → score 40-59\n" .
            "   • ratio 110-140% → score 20-39\n" .
            "   • ratio > 140% → score 0-19\n\n" .
            "Réponds en JSON :\n" .
            "{\n" .
            "  \"score\": nombre_0_à_100_OU_null_si_invalide,\n" .
            "  \"conseil\": \"phrase courte\",\n" .
            "  \"alerte\": null_ou_texte_si_prix_suspect,\n" .
            "  \"raisonnement\": \"Valeur estimée: X€. Ratio: Y%. Score: Z.\"\n" .
            "}";

        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un expert automobile spécialisé dans l\'évaluation des véhicules d\'occasion en France. ' .
                        'Tu connais les marques automobiles et leurs modèles, y compris les différentes finitions et versions (GT Line, GTI, RS, AMG, etc.). ' .
                        'Tu refuses d\'évaluer UNIQUEMENT si la marque est totalement inventée ou si le modèle appartient clairement à une autre marque. ' .
                        'Réponds uniquement en JSON valide.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 250
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Erreur API');
        }

        $result = json_decode($response, true);
        $content = trim($result['choices'][0]['message']['content'] ?? '');
        
        // Nettoyer le contenu
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);
        
        $jsonData = json_decode($content, true);
        
        // Log de debug pour voir la réponse IA
        error_log('Réponse IA brute: ' . $content);
        error_log('JSON décodé: ' . print_r($jsonData, true));
        
        if ($jsonData && array_key_exists('score', $jsonData)) {
            // Si score est null = véhicule impossible à évaluer
            if ($jsonData['score'] === null) {
                return [
                    'score' => null,
                    'label' => 'Impossible à évaluer',
                    'conseil' => $jsonData['conseil'] ?? 'Ce véhicule ne peut pas être évalué (modèle inconnu ou informations insuffisantes).',
                    'alerte' => null,
                    'raisonnement' => $jsonData['raisonnement'] ?? 'Véhicule inconnu'
                ];
            }
            
            // Ne PAS faire confiance au score de l'IA - Le recalculer nous-mêmes
            $score = null;
            $ratio = null;
            
            if (isset($jsonData['raisonnement'])) {
                // Extraire le ratio du raisonnement (c'est la seule chose fiable)
                if (preg_match('/Ratio:\s*\(.*?\).*?=\s*([\d.]+)%/i', $jsonData['raisonnement'], $matches)) {
                    $ratio = (float)$matches[1];
                    
                    // CALCUL PHP DU SCORE (plus fiable que l'IA)
                    if ($ratio < 30) {
                        $scoreMin = 95; $scoreMax = 100;
                    } elseif ($ratio < 50) {
                        $scoreMin = 80; $scoreMax = 94;
                    } elseif ($ratio < 70) {
                        $scoreMin = 65; $scoreMax = 79;
                    } elseif ($ratio < 90) {
                        $scoreMin = 50; $scoreMax = 64;
                    } elseif ($ratio <= 110) {
                        $scoreMin = 35; $scoreMax = 49;
                    } elseif ($ratio <= 140) {
                        $scoreMin = 15; $scoreMax = 34;
                    } else {
                        $scoreMin = 0; $scoreMax = 14;
                    }
                    
                    // Calculer un score proportionnel dans l'intervalle
                    $intervalWidth = $scoreMax - $scoreMin + 1;
                    if ($ratio < 30) {
                        // Affaire exceptionnelle : plus c'est bas, plus c'est haut
                        $position = max(0, min(1, (30 - $ratio) / 30));
                        $score = $scoreMin + round($position * $intervalWidth);
                    } elseif ($ratio < 50) {
                        $position = ($ratio - 30) / 20;
                        $score = $scoreMax - round($position * $intervalWidth);
                    } elseif ($ratio < 70) {
                        $position = ($ratio - 50) / 20;
                        $score = $scoreMax - round($position * $intervalWidth);
                    } elseif ($ratio < 90) {
                        $position = ($ratio - 70) / 20;
                        $score = $scoreMax - round($position * $intervalWidth);
                    } elseif ($ratio <= 110) {
                        $position = ($ratio - 90) / 20;
                        $score = $scoreMax - round($position * $intervalWidth);
                    } elseif ($ratio <= 140) {
                        $position = ($ratio - 111) / 29;
                        $score = $scoreMax - round($position * $intervalWidth);
                    } else {
                        $position = min(1, ($ratio - 140) / 60);
                        $score = $scoreMax - round($position * $intervalWidth);
                    }
                    
                    // S'assurer que le score est dans l'intervalle
                    $score = max($scoreMin, min($scoreMax, $score));
                    
                    $scoreIA = (int)$jsonData['score'];
                    error_log("📊 CALCUL PHP: Ratio={$ratio}% → Intervalle [{$scoreMin}-{$scoreMax}] → Score calculé: {$score} (IA avait dit: {$scoreIA})");
                } else {
                    error_log("❌ ERREUR: Impossible d'extraire le ratio du raisonnement");
                    $score = 50; // Valeur par défaut neutre
                }
            } else {
                error_log("❌ ERREUR: Pas de raisonnement fourni");
                $score = 50; // Valeur par défaut neutre
            }
            
            $score = max(0, min(100, $score));
            return [
                'score' => $score,
                'label' => $this->getLabel($score),
                'conseil' => $jsonData['conseil'] ?? $this->getConseilDefaut($score),
                'alerte' => $jsonData['alerte'] ?? null,
                'raisonnement' => $jsonData['raisonnement'] ?? 'Non fourni'
            ];
        }
        
        throw new Exception('Réponse invalide');
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
