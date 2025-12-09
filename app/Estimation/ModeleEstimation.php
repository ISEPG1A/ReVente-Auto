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

        $prompt = "Tu es un expert automobile strict. Avant d'estimer un véhicule, tu dois VÉRIFIER que la combinaison marque/modèle existe réellement.\n\n" .
            "Véhicule à estimer :\n" .
            "- Marque : " . $donnees['marque'] . "\n" .
            "- Modèle : " . $donnees['modele'] . "\n" .
            "- Année : " . $donnees['annee'] . "\n" .
            "- Kilométrage : " . ($donnees['kilometrage'] ?? 'Non spécifié') . " km\n" .
            "- Carburant : " . ($donnees['carburant'] ?? 'Non spécifié') . "\n" .
            "- Boîte de vitesse : " . ($donnees['boite'] ?? 'Non spécifié') . "\n" .
            "- État général : " . ($donnees['etat'] ?? 'Bon') . "\n\n" .
            "INSTRUCTIONS STRICTES :\n" .
            "1. Vérifie d'abord si ce modèle EXISTE RÉELLEMENT pour cette marque (ex: 'Mercedes xyz' n'existe pas)\n" .
            "2. Vérifie que l'année est cohérente avec la période de production du modèle\n" .
            "3. Si le modèle n'existe PAS ou si les informations sont incohérentes, réponds UNIQUEMENT : ERREUR\n" .
            "4. Si le véhicule existe, réponds au format JSON suivant (sans markdown, juste le JSON) :\n" .
            "{\"prix\": NUMBER, \"tendance\": \"hausse|stable|baisse\", \"tempsVente\": \"X-Y semaines\"}\n\n" .
            "Pour la TENDANCE, analyse le marché actuel de ce modèle précis :\n" .
            "- hausse : modèle recherché, demande croissante (ex: SUV, hybrides, électriques récents)\n" .
            "- stable : marché équilibré pour ce modèle\n" .
            "- baisse : modèle vieillissant, moins demandé, diesel ancien, etc.\n\n" .
            "Pour le TEMPS DE VENTE, estime en fonction :\n" .
            "- Popularité du modèle sur le marché de l'occasion\n" .
            "- Prix par rapport au marché\n" .
            "- Type de carburant (électrique/hybride = rapide, diesel ancien = lent)\n" .
            "- Kilométrage et état\n\n" .
            "EXEMPLES :\n" .
            "- Mercedes xyz 2016 → ERREUR\n" .
            "- Peugeot 208 2020 Essence → {\"prix\": 15000, \"tendance\": \"stable\", \"tempsVente\": \"2-3 semaines\"}\n" .
            "- Tesla Model 3 2022 → {\"prix\": 35000, \"tendance\": \"hausse\", \"tempsVente\": \"1-2 semaines\"}\n" .
            "- Renault Scenic 2015 Diesel → {\"prix\": 8000, \"tendance\": \"baisse\", \"tempsVente\": \"4-6 semaines\"}";

        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un expert automobile très strict spécialisé dans l\'estimation de véhicules d\'occasion en France. Tu connais TOUS les modèles de voitures existants et les tendances du marché actuel. Tu ne dois JAMAIS inventer un prix pour un véhicule qui n\'existe pas. Tu réponds uniquement en JSON valide ou ERREUR si le véhicule n\'existe pas.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.2,
            'max_tokens' => 100
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
