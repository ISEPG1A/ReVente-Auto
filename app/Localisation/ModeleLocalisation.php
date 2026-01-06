<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE LOCALISATION - GÉOLOCALISATION VIA NOMINATIM
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce modèle gère la récupération des coordonnées GPS via l'API Nominatim
 * d'OpenStreetMap. Les résultats sont mis en cache pour éviter les appels
 * répétés à l'API externe et optimiser les performances.
 * 
 * Fonctionnalités :
 * - Appel à l'API Nominatim pour géocoder une ville
 * - Mise en cache des résultats (24h)
 * - Gestion des erreurs et timeouts
 * - Respect des conditions d'utilisation de Nominatim
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * @see     https://operations.osmfoundation.org/policies/nominatim/ Politique d'usage
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ModeleLocalisation {
    
    private const API_GOUV = 'https://geo.api.gouv.fr/communes';
    private const NOMINATIM_API = 'https://nominatim.openstreetmap.org/search';
    private const USER_AGENT = 'ReVente-Auto/1.0';
    private const CACHE_DURATION = 86400; // 24 heures
    
    /**
     * Récupère les coordonnées GPS d'une ville
     * 
     * @param string $ville Nom de la ville
     * @param string|null $codePostal Code postal pour améliorer la précision (optionnel)
     * @return array|null Coordonnées [lat, lon, display_name] ou null
     */
    public function obtenirCoordonnees($ville, $codePostal = null) {
        // Vérification du cache (avec code postal dans la clé si fourni)
        $cacheKey = 'geo_' . md5($ville . ($codePostal ?? ''));
        $cached = $this->obtenirCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Essayer d'abord avec l'API Gouv (plus précise pour la France)
        $result = $this->obtenirCoordoneesApiGouv($ville, $codePostal);
        
        // Fallback sur Nominatim si API Gouv échoue
        if ($result === null) {
            $result = $this->obtenirCoordoneesNominatim($ville);
        }
        
        // Mise en cache si résultat trouvé
        if ($result !== null) {
            $this->sauvegarderCache($cacheKey, $result);
        }
        
        return $result;
    }
    
    /**
     * Recherche via l'API Gouv (communes françaises)
     * 
     * @param string $ville Nom de la ville
     * @param string|null $codePostal Code postal pour filtrer (optionnel)
     * @return array|null Coordonnées ou null
     */
    private function obtenirCoordoneesApiGouv($ville, $codePostal = null) {
        try {
            // Nettoyer le nom de la ville
            $villeClean = trim(str_replace([', France', ',France'], '', $ville));
            
            $params = [
                'nom' => $villeClean,
                'fields' => 'nom,centre,codesPostaux,contour',
                'format' => 'json',
                'limit' => 5  // Augmenter pour filtrer par code postal si nécessaire
            ];
            
            // Ajouter le code postal si fourni
            if ($codePostal) {
                $params['codePostal'] = $codePostal;
                $params['limit'] = 1;  // Si on a le CP, 1 résultat suffit
            }
            
            $url = self::API_GOUV . '?' . http_build_query($params);
            
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'Accept: application/json',
                    'timeout' => 5
                ]
            ];
            
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (empty($data) || !isset($data[0]['centre'])) {
                return null;
            }
            
            $commune = $data[0];
            $coords = $commune['centre']['coordinates']; // [lon, lat]
            
            // Calculer le boundingbox à partir du contour si disponible
            $boundingbox = null;
            if (isset($commune['contour'])) {
                $contour = $commune['contour'];
                if ($contour['type'] === 'Polygon' && !empty($contour['coordinates'][0])) {
                    $lats = array_column($contour['coordinates'][0], 1);
                    $lons = array_column($contour['coordinates'][0], 0);
                    $boundingbox = [
                        min($lats), // latMin
                        max($lats), // latMax
                        min($lons), // lonMin
                        max($lons)  // lonMax
                    ];
                }
            }
            
            // Si pas de contour, créer un boundingbox approximatif (environ 5km de rayon)
            if ($boundingbox === null) {
                $latOffset = 0.045; // environ 5km
                $lonOffset = 0.045;
                $boundingbox = [
                    $coords[1] - $latOffset,
                    $coords[1] + $latOffset,
                    $coords[0] - $lonOffset,
                    $coords[0] + $lonOffset
                ];
            }
            
            return [
                'lat' => (float) $coords[1],
                'lon' => (float) $coords[0],
                'display_name' => $commune['nom'] . ', France',
                'boundingbox' => $boundingbox,
                'contour' => isset($commune['contour']) ? $commune['contour'] : null,
                'type' => 'city',
                'importance' => 1.0
            ];
            
        } catch (Exception $e) {
            error_log("Exception API Gouv: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Recherche via Nominatim (fallback)
     * 
     * @param string $ville Nom de la ville
     * @return array|null Coordonnées ou null
     */
    private function obtenirCoordoneesNominatim($ville) {
        try {
            $rechercheVille = $ville;
            if (stripos($ville, 'france') === false) {
                $rechercheVille = $ville . ', France';
            }
            
            $url = self::NOMINATIM_API . '?' . http_build_query([
                'format' => 'json',
                'q' => $rechercheVille,
                'countrycodes' => 'fr',
                'limit' => 1,
                'addressdetails' => 1
            ]);
            
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: ' . self::USER_AGENT,
                        'Accept: application/json'
                    ],
                    'timeout' => 5
                ]
            ];
            
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (empty($data) || !isset($data[0])) {
                return null;
            }
            
            return [
                'lat' => (float) $data[0]['lat'],
                'lon' => (float) $data[0]['lon'],
                'display_name' => $data[0]['display_name'] ?? $ville,
                'boundingbox' => $data[0]['boundingbox'] ?? null,
                'type' => $data[0]['type'] ?? 'unknown',
                'importance' => $data[0]['importance'] ?? 0
            ];
            
        } catch (Exception $e) {
            error_log("Exception Nominatim: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère une valeur du cache
     * 
     * @param string $key Clé de cache
     * @return mixed|null Valeur en cache ou null
     */
    private function obtenirCache($key) {
        $cacheFile = sys_get_temp_dir() . '/revente_geo_' . $key . '.json';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        // Vérifier l'expiration
        if (time() - filemtime($cacheFile) > self::CACHE_DURATION) {
            @unlink($cacheFile);
            return null;
        }
        
        $content = @file_get_contents($cacheFile);
        return $content ? json_decode($content, true) : null;
    }
    
    /**
     * Sauvegarde une valeur en cache
     * 
     * @param string $key Clé de cache
     * @param mixed $value Valeur à mettre en cache
     * @return bool Succès de l'opération
     */
    private function sauvegarderCache($key, $value) {
        $cacheFile = sys_get_temp_dir() . '/revente_geo_' . $key . '.json';
        return @file_put_contents($cacheFile, json_encode($value)) !== false;
    }
}
