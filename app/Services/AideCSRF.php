<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * AIDE CSRF - PROTECTION CONTRE LES ATTAQUES CROSS-SITE REQUEST FORGERY
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe fournit des méthodes utilitaires pour intégrer facilement
 * la protection CSRF dans les formulaires HTML et les requêtes AJAX.
 * 
 * Utilisation :
 * - Dans un formulaire HTML : <?= AideCSRF::champFormulaire() ?>
 * - Dans le <head> pour AJAX : <?= AideCSRF::baliseMetaDonnees() ?>
 * - En JavaScript : AideCSRF::obtenirJeton()
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     GestionnaireSession Pour la gestion des tokens CSRF
 * ═══════════════════════════════════════════════════════════════════════════
 */

class AideCSRF {
    
    /**
     * Génère un champ input hidden avec le jeton CSRF pour les formulaires HTML
     * 
     * À insérer dans chaque formulaire <form> pour le protéger contre les
     * attaques CSRF. Le token est automatiquement vérifié côté serveur.
     * 
     * @return string Balise HTML <input type="hidden"> avec le jeton
     * @example <?= AideCSRF::champFormulaire() ?>
     */
    public static function champFormulaire(): string {
        $jeton = GestionnaireSession::genererTokenCSRF();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($jeton, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Génère une balise meta avec le jeton CSRF pour les requêtes AJAX
     * 
     * À placer dans la section <head> du document HTML.
     * Le JavaScript peut ensuite récupérer ce token pour l'inclure
     * dans les en-têtes des requêtes AJAX (X-CSRF-Token).
     * 
     * @return string Balise HTML <meta> avec le jeton
     * @example <head><?= AideCSRF::baliseMetaDonnees() ?></head>
     */
    public static function baliseMetaDonnees(): string {
        $jeton = GestionnaireSession::genererTokenCSRF();
        return '<meta name="csrf-token" content="' . htmlspecialchars($jeton, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Retourne le jeton CSRF brut
     * 
     * Utile pour passer le token directement à du JavaScript
     * ou pour des cas d'usage personnalisés.
     * 
     * @return string Jeton CSRF
     */
    public static function obtenirJeton(): string {
        return GestionnaireSession::genererTokenCSRF();
    }

    /**
     * Vérifie le token CSRF depuis les en-têtes ou le body de la requête
     * 
     * Cherche le token dans :
     * - En-tête HTTP X-CSRF-Token (pour AJAX)
     * - Corps JSON (champ csrf_token)
     * - POST (champ csrf_token)
     * 
     * @return bool true si le token est valide
     */
    public static function verifierTokenDepuisRequete(): bool {
        // Chercher dans les en-têtes HTTP
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        // Si pas dans les en-têtes, chercher dans le body JSON
        if (empty($token)) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true);
                if (is_array($data)) {
                    $token = $data['csrf_token'] ?? '';
                }
            }
        }
        
        // Si pas dans JSON, chercher dans POST
        if (empty($token)) {
            $token = $_POST['csrf_token'] ?? '';
        }
        
        // Valider le token
        return GestionnaireSession::validerTokenCSRF($token);
    }
}
