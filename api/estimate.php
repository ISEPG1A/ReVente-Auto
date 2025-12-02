<?php
/**
 * API d'estimation de prix de véhicule via OpenAI
 * 
 * Reçoit les caractéristiques d'un véhicule et retourne une estimation de prix.
 */

require_once __DIR__ . '/config.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    envoyerJSON(['error' => 'Méthode non autorisée'], 405);
}

// Récupérer les données
$donnees = lireCorpsJSON();

// Validation basique
if (empty($donnees['marque']) || empty($donnees['modele']) || empty($donnees['annee'])) {
    envoyerJSON(['error' => 'Données incomplètes'], 400);
}

// Configuration de l'API OpenAI
// ⚠️ IMPORTANT : Remplacez cette clé par votre propre clé API OpenAI
// Ne committez jamais votre vraie clé API sur un dépôt public !
$apiKey = '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'; 

if ($apiKey === 'VOTRE_CLE_API_OPENAI_ICI') {
    envoyerJSON(['error' => 'Clé API OpenAI non configurée. Veuillez configurer la clé dans api/estimate.php'], 500);
}

// Construction du prompt pour l'IA
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

// Appel à l'API OpenAI
$url = 'https://api.openai.com/v1/chat/completions';
$data = [
    'model' => 'gpt-4o-mini', // Ou gpt-4 si disponible
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
    'temperature' => 0.3, // Faible température pour une réponse plus déterministe
    'max_tokens' => 10
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    envoyerJSON(['error' => 'Erreur de connexion à l\'API IA: ' . $curlError], 500);
}

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $errorMessage = $errorData['error']['message'] ?? 'Erreur inconnue de l\'API IA';
    envoyerJSON(['error' => 'Erreur API IA: ' . $errorMessage], 500);
}

// Traitement de la réponse
$result = json_decode($response, true);
$content = trim($result['choices'][0]['message']['content'] ?? '');

// Nettoyage de la réponse pour ne garder que le prix ou ???
if ($content === '???') {
    envoyerJSON(['prix' => null, 'message' => 'Estimation impossible']);
} else {
    // On essaie d'extraire un nombre de la réponse au cas où l'IA serait bavarde
    if (preg_match('/(\d[\d\s]*)/', $content, $matches)) {
        $prix = str_replace(' ', '', $matches[1]);
        envoyerJSON(['prix' => (int)$prix]);
    } else {
        envoyerJSON(['prix' => null, 'message' => 'Format de réponse invalide']);
    }
}
