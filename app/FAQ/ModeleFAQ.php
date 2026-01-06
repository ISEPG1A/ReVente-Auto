<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE FAQ - Gestion des questions fréquemment posées
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère toutes les opérations CRUD pour la FAQ administrable.
 * Permet à l'administrateur d'ajouter, modifier, supprimer et réorganiser
 * les questions et réponses de la FAQ.
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../Commun/BaseDeDonnees.php';

class ModeleFAQ
{
    private PDO $connexion;

    public function __construct()
    {
        $this->connexion = BaseDeDonnees::obtenirConnexion();
    }

    /**
     * Récupère toutes les questions FAQ
     * 
     * @param bool $seulementActives Si true, ne retourne que les questions actives
     * @param string|null $categorie Filtre par catégorie (optionnel)
     * @return array Liste des questions FAQ
     */
    public function obtenirTous(bool $seulementActives = true, ?string $categorie = null): array
    {
        $sql = "SELECT * FROM faq WHERE 1=1";
        $params = [];

        if ($seulementActives) {
            $sql .= " AND is_active = 1";
        }

        if ($categorie !== null) {
            $sql .= " AND category = :categorie";
            $params['categorie'] = $categorie;
        }

        $sql .= " ORDER BY display_order ASC, created_at DESC";

        $stmt = $this->connexion->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une question FAQ par son ID
     * 
     * @param int $id Identifiant de la question
     * @return array|null Question FAQ ou null si introuvable
     */
    public function obtenirParId(int $id): ?array
    {
        $sql = "SELECT * FROM faq WHERE id = :id";
        $stmt = $this->connexion->prepare($sql);
        $stmt->execute(['id' => $id]);

        $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultat ?: null;
    }

    /**
     * Récupère toutes les catégories disponibles
     * 
     * @return array Liste des catégories uniques
     */
    public function obtenirCategories(): array
    {
        $sql = "SELECT DISTINCT category FROM faq ORDER BY category ASC";
        $stmt = $this->connexion->query($sql);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Ajoute une nouvelle question FAQ
     * 
     * @param string $question Texte de la question
     * @param string $reponse Texte de la réponse
     * @param string $categorie Catégorie de la question
     * @param int $ordre Ordre d'affichage
     * @param bool $active Si la question est active ou non
     * @return int ID de la question créée
     * @throws Exception Si l'insertion échoue
     */
    public function ajouter(
        string $question,
        string $reponse,
        string $categorie = 'Général',
        int $ordre = 0,
        bool $active = true
    ): int {
        $sql = "INSERT INTO faq (question, answer, category, display_order, is_active) 
                VALUES (:question, :answer, :category, :display_order, :is_active)";

        $stmt = $this->connexion->prepare($sql);
        $stmt->execute([
            'question' => $question,
            'answer' => $reponse,
            'category' => $categorie,
            'display_order' => $ordre,
            'is_active' => $active ? 1 : 0
        ]);

        $id = (int)$this->connexion->lastInsertId();

        if ($id === 0) {
            throw new Exception('Échec de la création de la question FAQ');
        }

        return $id;
    }

    /**
     * Modifie une question FAQ existante
     * 
     * @param int $id Identifiant de la question
     * @param string $question Texte de la question
     * @param string $reponse Texte de la réponse
     * @param string $categorie Catégorie de la question
     * @param int $ordre Ordre d'affichage
     * @param bool $active Si la question est active ou non
     * @return bool True si la modification a réussi
     */
    public function modifier(
        int $id,
        string $question,
        string $reponse,
        string $categorie,
        int $ordre,
        bool $active
    ): bool {
        $sql = "UPDATE faq 
                SET question = :question, 
                    answer = :answer, 
                    category = :category, 
                    display_order = :display_order, 
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $stmt = $this->connexion->prepare($sql);
        $resultat = $stmt->execute([
            'id' => $id,
            'question' => $question,
            'answer' => $reponse,
            'category' => $categorie,
            'display_order' => $ordre,
            'is_active' => $active ? 1 : 0
        ]);

        return $resultat && $stmt->rowCount() > 0;
    }

    /**
     * Supprime une question FAQ
     * 
     * @param int $id Identifiant de la question
     * @return bool True si la suppression a réussi
     */
    public function supprimer(int $id): bool
    {
        $sql = "DELETE FROM faq WHERE id = :id";
        $stmt = $this->connexion->prepare($sql);
        $resultat = $stmt->execute(['id' => $id]);

        return $resultat && $stmt->rowCount() > 0;
    }

    /**
     * Change l'ordre d'affichage d'une question
     * 
     * @param int $id Identifiant de la question
     * @param int $nouvelOrdre Nouvel ordre d'affichage
     * @return bool True si le changement a réussi
     */
    public function changerOrdre(int $id, int $nouvelOrdre): bool
    {
        $sql = "UPDATE faq SET display_order = :ordre WHERE id = :id";
        $stmt = $this->connexion->prepare($sql);
        $resultat = $stmt->execute([
            'id' => $id,
            'ordre' => $nouvelOrdre
        ]);

        return $resultat && $stmt->rowCount() > 0;
    }

    /**
     * Active ou désactive une question FAQ
     * 
     * @param int $id Identifiant de la question
     * @param bool $active État actif/inactif
     * @return bool True si le changement a réussi
     */
    public function basculerActivation(int $id, bool $active): bool
    {
        $sql = "UPDATE faq SET is_active = :active WHERE id = :id";
        $stmt = $this->connexion->prepare($sql);
        $resultat = $stmt->execute([
            'id' => $id,
            'active' => $active ? 1 : 0
        ]);

        return $resultat && $stmt->rowCount() > 0;
    }

    /**
     * Compte le nombre total de questions FAQ
     * 
     * @param bool $seulementActives Si true, compte uniquement les questions actives
     * @return int Nombre de questions
     */
    public function compter(bool $seulementActives = false): int
    {
        $sql = "SELECT COUNT(*) FROM faq";
        
        if ($seulementActives) {
            $sql .= " WHERE is_active = 1";
        }

        $stmt = $this->connexion->query($sql);
        return (int)$stmt->fetchColumn();
    }
}

