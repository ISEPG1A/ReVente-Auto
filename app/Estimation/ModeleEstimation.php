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

        $prompt = "Estime le prix de vente d'un véhicule d'occasion en France avec les caractéristiques suivantes :\n" .
            "- Marque : " . $donnees['marque'] . "\n" .
            "- Modèle : " . $donnees['modele'] . "\n" .
            "- Année : " . $donnees['annee'] . "\n" .
            "- Kilométrage : " . ($donnees['kilometrage'] ?? 'Non spécifié') . " km\n" .
            "- Carburant : " . ($donnees['carburant'] ?? 'Non spécifié') . "\n" .
            "- Boîte de vitesse : " . ($donnees['boite'] ?? 'Non spécifié') . "\n" .
            "- État général : " . ($donnees['etat'] ?? 'Bon') . "\n\n" .
            "Réponds UNIQUEMENT par le prix estimé en euros (un seul nombre, sans symbole € ni texte). " .
            "Si tu ne peux pas faire d'estimation fiable ou si le véhicule n'existe pas, réponds exactement '???'.";

        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un expert en automobile capable d\'estimer la valeur de marché des véhicules d\'occasion en France.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 10
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

        if ($content === '???') {
            return ['prix' => null, 'message' => 'Estimation impossible'];
        } else {
            if (preg_match('/(\d[\d\s]*)/', $content, $matches)) {
                $prix = str_replace(' ', '', $matches[1]);
                return ['prix' => (int)$prix];
            } else {
                return ['prix' => null, 'message' => 'Format de réponse invalide'];
            }
        }
    }
}
