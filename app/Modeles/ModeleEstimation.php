<?php

class ModeleEstimation {
    private $apiKey;

    public function __construct() {
        $config = require __DIR__ . '/../../config.php';
        $this->apiKey = $config['openai_api_key'] ?? '';
    }

    public function estimer($donnees) {
        if (empty($this->apiKey) || $this->apiKey === 'VOTRE_CLE_API_OPENAI_ICI') {
            throw new Exception('Clé API OpenAI non configurée.');
        }
        
        // Normaliser les données pour le calcul du seed
        $marque = strtolower(trim($donnees['marque'] ?? ''));
        $modele = strtolower(trim($donnees['modele'] ?? ''));
        $annee = (int)($donnees['annee'] ?? date('Y'));
        $km = round((int)($donnees['kilometrage'] ?? 0), -3); // Arrondir à 1000 près
        $carburant = strtolower(trim($donnees['carburant'] ?? ''));
        $etat = strtolower(trim($donnees['etat'] ?? 'bon'));
        
        // Créer un hash déterministe pour des résultats reproductibles
        $seedData = $marque . '|' . $modele . '|' . $annee . '|' . $km . '|' . $carburant . '|' . $etat;
        $seed = abs(crc32($seedData));

        $prompt = "TÂCHE: Estimer la valeur d'un véhicule d'occasion sur le marché français en " . date('Y') . ".\n\n" .
            "VÉHICULE À ÉVALUER:\n" .
            "- Marque: " . $donnees['marque'] . "\n" .
            "- Modèle: " . $donnees['modele'] . "\n" .
            "- Année: " . $donnees['annee'] . "\n" .
            "- Kilométrage: " . ($donnees['kilometrage'] ?? 'Non spécifié') . " km\n" .
            "- Carburant: " . ($donnees['carburant'] ?? 'Non spécifié') . "\n" .
            "- Boîte de vitesse: " . ($donnees['boite'] ?? 'Non spécifié') . "\n" .
            "- État général: " . ($donnees['etat'] ?? 'Bon') . "\n\n" .
            "RÈGLES STRICTES À SUIVRE:\n" .
            "1. VÉRIFICATION OBLIGATOIRE: Le modèle \"" . $donnees['modele'] . "\" de la marque \"" . $donnees['marque'] . "\" DOIT exister.\n" .
            "2. Si ce modèle N'EXISTE PAS ou est INVENTÉ → réponds: ERREUR\n" .
            "3. Si l'année est INCOHÉRENTE avec la période de production → réponds: ERREUR\n" .
            "4. Si le véhicule EXISTE → calcule le prix basé sur la cote Argus/La Centrale.\n\n" .
            "CRITÈRES D'ESTIMATION:\n" .
            "- Cote Argus de référence pour ce modèle/année\n" .
            "- Décote kilométrique: +/- 100€ par tranche de 10 000 km vs moyenne\n" .
            "- Ajustement état: Excellent (+5%), Bon (0%), Moyen (-10%), Mauvais (-20%)\n" .
            "- Carburant: électrique/hybride = valorisé, diesel ancien = décoté\n\n" .
            "TENDANCE:\n" .
            "- hausse: modèle très demandé (SUV, électrique, hybride)\n" .
            "- stable: demande normale pour ce segment\n" .
            "- baisse: modèle vieillissant ou diesel\n\n" .
            "RÉPONSE OBLIGATOIRE (JSON sans markdown):\n" .
            "Si véhicule existe: {\"prix\": NOMBRE, \"tendance\": \"hausse|stable|baisse\", \"tempsVente\": \"X-Y semaines\"}\n" .
            "Si véhicule n'existe pas: ERREUR";

        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un expert automobile certifié, spécialisé dans l\'évaluation de véhicules d\'occasion en France. Tu as accès aux données Argus et La Centrale. Tu connais TOUS les modèles de véhicules existants et leurs années de production. Tu ne dois JAMAIS estimer un véhicule fictif ou inventé. Tes estimations doivent être précises et cohérentes: le même véhicule doit toujours avoir la même estimation. Réponds UNIQUEMENT en JSON valide ou "ERREUR".'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0,
            'max_tokens' => 120,
            'seed' => $seed
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
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('Erreur de connexion à l\'API IA: ' . $curlError);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Erreur inconnue de l\'API IA';
            throw new Exception('Erreur API IA: ' . $errorMessage);
        }

        $result = json_decode($response, true);
        $content = trim($result['choices'][0]['message']['content'] ?? '');

        // Vérifie si c'est une erreur
        if (strtoupper($content) === 'ERREUR' || $content === '???' || strpos(strtoupper($content), 'ERREUR') !== false) {
            return ['prix' => null, 'message' => 'Estimation impossible'];
        }
        
        // Essaie de parser le JSON
        // Nettoyer le contenu (enlever les éventuels backticks markdown)
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);
        
        $jsonData = json_decode($content, true);
        
        if ($jsonData && isset($jsonData['prix'])) {
            $response = [
                'prix' => (int)$jsonData['prix'],
                'tendance' => $jsonData['tendance'] ?? 'stable',
                'tempsVente' => $jsonData['tempsVente'] ?? '2-4 semaines'
            ];
            return $response;
        } else {
            // Fallback : essayer d'extraire juste le prix si le JSON est invalide
            if (preg_match('/(\d[\d\s]*)/', $content, $matches)) {
                $prix = str_replace(' ', '', $matches[1]);
                return [
                    'prix' => (int)$prix,
                    'tendance' => 'stable',
                    'tempsVente' => '2-4 semaines'
                ];
            } else {
                return ['prix' => null, 'message' => 'Format de réponse invalide'];
            }
        }
    }
}
