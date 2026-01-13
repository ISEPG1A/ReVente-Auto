<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE POLITIQUE DE CONFIDENTIALITÉ
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère les opérations CRUD pour les sections de la politique de confidentialité.
 * Structure simple : titre + contenu + ordre (comme FAQ)
 * 
 * @author  mat
 * @version 1.0
 */

class ModelePolitiqueConfidentialite {
    
    private PDO $db;
    
    public function __construct() {
        $this->db = BaseDeDonnees::obtenirConnexion();
    }
    
    /**
     * Récupère toutes les sections
     * @param bool $publieSeulement Si true, ne retourne que les sections publiées
     * @return array
     */
    public function obtenirTout(bool $publieSeulement = true): array {
        $sql = "SELECT * FROM politique_confidentialite";
        if ($publieSeulement) {
            $sql .= " WHERE statut = 'publie'";
        }
        $sql .= " ORDER BY ordre ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère une section par son ID
     * @param int $id
     * @return array|null
     */
    public function obtenirParId(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM politique_confidentialite WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Crée une nouvelle section
     * @param array $donnees [titre, contenu, statut?]
     * @return int|false L'ID de la nouvelle section ou false
     */
    public function creer(array $donnees): int|false {
        // Récupérer l'ordre max actuel
        $stmt = $this->db->query("SELECT COALESCE(MAX(ordre), 0) + 1 FROM politique_confidentialite");
        $nouvelOrdre = $stmt->fetchColumn();
        
        $stmt = $this->db->prepare("
            INSERT INTO politique_confidentialite (titre, contenu, ordre, statut) 
            VALUES (?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $donnees['titre'],
            $donnees['contenu'],
            $nouvelOrdre,
            $donnees['statut'] ?? 'publie'
        ]);
        
        return $success ? (int)$this->db->lastInsertId() : false;
    }
    
    /**
     * Modifie une section existante
     * @param int $id
     * @param array $donnees [titre?, contenu?, statut?]
     * @return bool
     */
    public function modifier(int $id, array $donnees): bool {
        $champs = [];
        $valeurs = [];
        
        if (isset($donnees['titre'])) {
            $champs[] = "titre = ?";
            $valeurs[] = $donnees['titre'];
        }
        if (isset($donnees['contenu'])) {
            $champs[] = "contenu = ?";
            $valeurs[] = $donnees['contenu'];
        }
        if (isset($donnees['statut'])) {
            $champs[] = "statut = ?";
            $valeurs[] = $donnees['statut'];
        }
        
        if (empty($champs)) {
            return false;
        }
        
        $valeurs[] = $id;
        $sql = "UPDATE politique_confidentialite SET " . implode(', ', $champs) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($valeurs);
    }
    
    /**
     * Supprime une section
     * @param int $id
     * @return bool
     */
    public function supprimer(int $id): bool {
        // Récupérer l'ordre actuel
        $section = $this->obtenirParId($id);
        if (!$section) {
            return false;
        }
        
        // Supprimer la section
        $stmt = $this->db->prepare("DELETE FROM politique_confidentialite WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        // Réordonner les sections restantes
        if ($success) {
            $stmt = $this->db->prepare("
                UPDATE politique_confidentialite 
                SET ordre = ordre - 1 
                WHERE ordre > ?
            ");
            $stmt->execute([$section['ordre']]);
        }
        
        return $success;
    }
    
    /**
     * Déplace une section (change son ordre)
     * @param int $id
     * @param string $direction 'monter' ou 'descendre'
     * @return bool
     */
    public function deplacer(int $id, string $direction): bool {
        $section = $this->obtenirParId($id);
        if (!$section) {
            return false;
        }
        
        $ordreActuel = (int)$section['ordre'];
        $nouvelOrdre = $direction === 'monter' ? $ordreActuel - 1 : $ordreActuel + 1;
        
        if ($nouvelOrdre < 1) {
            return false;
        }
        
        // Vérifier qu'il y a une section à cette position
        $stmt = $this->db->prepare("SELECT id FROM politique_confidentialite WHERE ordre = ?");
        $stmt->execute([$nouvelOrdre]);
        $autreSection = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$autreSection) {
            return false;
        }
        
        // Échanger les positions
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE politique_confidentialite SET ordre = ? WHERE id = ?");
            $stmt->execute([$nouvelOrdre, $id]);
            $stmt->execute([$ordreActuel, $autreSection['id']]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
