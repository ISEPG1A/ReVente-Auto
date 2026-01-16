<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE ÉQUIPE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère les requêtes liées aux membres de l'équipe (administrateurs)
 */
class ModeleEquipe {
    
    /**
     * Instance de connexion PDO
     */
    private PDO $bdd;

    /**
     * Constructeur - initialise la connexion à la base de données
     */
    public function __construct() {
        $this->bdd = BaseDeDonnees::obtenirConnexion();
    }

    /**
     * Récupère tous les utilisateurs ayant le rôle admin
     * 
     * @return array Liste des membres admin avec leurs informations
     */
    public function obtenirAdmins(): array {
        $sql = 'SELECT id, last_name, first_name, poste, avatar_path, email, phone
                FROM users 
                WHERE role = :role 
                ORDER BY id';
        
        $stmt = $this->bdd->prepare($sql);
        $stmt->execute(['role' => 'admin']);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
