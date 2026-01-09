<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR DE BASE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Classe abstraite dont héritent tous les contrôleurs de pages.
 * Fournit les méthodes communes : rendu de vues, redirections, etc.
 */
abstract class ControleurBase {
    
    /**
     * Données à passer à la vue
     */
    protected array $donnees = [];
    
    /**
     * Titre de la page
     */
    protected string $titrePage = 'ReVente-Auto';
    
    /**
     * Identifiant de la page active (pour la navigation)
     */
    protected string $pageActive = '';
    
    /**
     * Chemin de base de l'application
     */
    protected string $cheminBase = '/';
    
    /**
     * Constructeur - initialise les données de base
     */
    public function __construct() {
        // Démarrer la session si nécessaire
        if (session_status() === PHP_SESSION_NONE) {
            GestionnaireSession::demarrerSession();
        }
        
        // Calculer le chemin de base
        $this->calculerCheminBase();
    }
    
    /**
     * Calcule le chemin de base de l'application
     */
    private function calculerCheminBase(): void {
        $nomScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        
        if (strpos($nomScript, '/public/') !== false) {
            $this->cheminBase = substr($nomScript, 0, strpos($nomScript, '/public/')) . '/public/';
        } else {
            $this->cheminBase = '/';
        }
    }
    
    /**
     * Affiche une vue avec le layout principal
     * 
     * @param string $vue Chemin de la vue (ex: 'accueil', 'vehicule/galerie', 'auth/connexion')
     * @param array $donnees Données à passer à la vue
     */
    protected function rendu(string $vue, array $donnees = []): void {
        // Fusionner les données passées avec les données du contrôleur
        $this->donnees = array_merge($this->donnees, $donnees);
        
        // Variables pour le layout
        $title = $this->titrePage;
        $current = $this->pageActive;
        $view = __DIR__ . '/../../views/pages/' . $vue . '.php';
        
        // Extraire les données pour les rendre accessibles dans la vue
        extract($this->donnees);
        
        // Vérifier que la vue existe
        if (!file_exists($view)) {
            http_response_code(404);
            $view = __DIR__ . '/../../views/pages/erreur/404.php';
            $title = 'Page introuvable';
        }
        
        // Inclure le layout principal (qui inclura la vue)
        include __DIR__ . '/../../views/layouts/principal.php';
    }
    
    /**
     * Redirige vers une autre URL
     * 
     * @param string $url URL de destination
     * @param int $code Code HTTP (301 ou 302)
     */
    protected function rediriger(string $url, int $code = 302): void {
        http_response_code($code);
        header('Location: ' . $this->cheminBase . ltrim($url, '/'));
        exit;
    }
    
    /**
     * Vérifie si l'utilisateur est connecté
     */
    protected function estConnecte(): bool {
        return GestionnaireSession::estConnecte();
    }
    
    /**
     * Récupère l'utilisateur connecté
     */
    protected function obtenirUtilisateur(): ?array {
        return GestionnaireSession::obtenirUtilisateur();
    }
    
    /**
     * Exige que l'utilisateur soit connecté, sinon redirige
     */
    protected function exigerConnexion(): void {
        if (!$this->estConnecte()) {
            $this->rediriger('connexion');
        }
    }
    
    /**
     * Méthode abstraite que chaque contrôleur doit implémenter
     */
    abstract public function index(): void;
}
