<?php

/**
 * Gestionnaire de limite de taux (Rate Limiting)
 * Permet de limiter le nombre de tentatives d'actions (ex: login) par IP.
 */
class GestionnaireLimiteTaux {
    private const CONFIG = [
        'login' => ['max' => 5, 'temps' => 900], // 5 essais / 15 min
        'upload' => ['max' => 50, 'temps' => 3600], // 50 uploads / 1 heure (pour tests et modifications multiples)
        'password_reset' => ['max' => 1, 'temps' => 30], // 1 demande / 30 secondes
        'default' => ['max' => 10, 'temps' => 60]
    ];
    private const DOSSIER_STOCKAGE = __DIR__ . '/../../stockage/rate_limit/';

    /**
     * Vérifie si l'IP actuelle est autorisée à faire une tentative
     * @param string $action Identifiant de l'action (ex: 'login')
     * @return bool True si autorisé, False si bloqué
     */
    public static function verifierTentative($action = 'login') {
        $ip = $_SERVER['REMOTE_ADDR'];
        $fichier = self::obtenirCheminFichier($ip, $action);

        if (!file_exists($fichier)) {
            return true;
        }

        $donnees = json_decode(file_get_contents($fichier), true);
        
        // Si le temps de blocage est passé, on reset
        if (time() > $donnees['expiration']) {
            unlink($fichier);
            return true;
        }

        $config = self::CONFIG[$action] ?? self::CONFIG['default'];
        return $donnees['tentatives'] < $config['max'];
    }

    /**
     * Enregistre une tentative échouée ou une action
     * @param string $action Identifiant de l'action
     * @param int $nombre Nombre de tentatives à ajouter (par défaut 1)
     * @return int Nombre de tentatives restantes
     */
    public static function ajouterTentative($action = 'login', $nombre = 1) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $fichier = self::obtenirCheminFichier($ip, $action);
        
        $config = self::CONFIG[$action] ?? self::CONFIG['default'];
        
        $donnees = ['tentatives' => 0, 'expiration' => time() + $config['temps']];
        
        if (file_exists($fichier)) {
            $contenu = json_decode(file_get_contents($fichier), true);
            // Si le fichier existe mais était expiré (cas limite), on repart à 0
            if (time() <= $contenu['expiration']) {
                $donnees = $contenu;
            }
        }

        $donnees['tentatives'] += $nombre;
        
        // S'assurer que le dossier existe
        if (!is_dir(dirname($fichier))) {
            mkdir(dirname($fichier), 0777, true);
        }

        file_put_contents($fichier, json_encode($donnees));

        return max(0, $config['max'] - $donnees['tentatives']);
    }

    /**
     * Réinitialise le compteur après un succès
     */
    public static function reinitialiser($action = 'login') {
        $ip = $_SERVER['REMOTE_ADDR'];
        $fichier = self::obtenirCheminFichier($ip, $action);
        if (file_exists($fichier)) {
            unlink($fichier);
        }
    }

    /**
     * Génère le chemin du fichier de stockage pour une IP
     */
    private static function obtenirCheminFichier($ip, $action) {
        // Hash de l'IP pour éviter les problèmes de caractères dans les noms de fichiers
        $hash = md5($ip . $action);
        return self::DOSSIER_STOCKAGE . $hash . '.json';
    }
}
