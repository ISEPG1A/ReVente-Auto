<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE FAQ - GESTION DES QUESTIONS FRÉQUENTES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Structure simple :
 * - Question (texte)
 * - Réponse (HTML)
 * - Ordre (pour le tri)
 * - Statut (brouillon/publié)
 * 
 * Table : faq
 */
class ModeleFAQ {
    
    private PDO $bdd;

    public function __construct() {
        $this->bdd = BaseDeDonnees::obtenirConnexion();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // LECTURE
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Récupère toutes les questions FAQ
     * 
     * @param bool $uniquementPubliees Si true, retourne seulement les questions publiées
     * @return array Liste des questions
     */
    public function obtenirTout(bool $uniquementPubliees = true): array {
        try {
            $sql = 'SELECT * FROM faq';
            if ($uniquementPubliees) {
                $sql .= ' WHERE statut = :statut';
            }
            $sql .= ' ORDER BY ordre ASC';
            
            $stmt = $this->bdd->prepare($sql);
            if ($uniquementPubliees) {
                $stmt->execute(['statut' => 'publie']);
            } else {
                $stmt->execute();
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Erreur ModeleFAQ::obtenirTout - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère une question par son ID
     * 
     * @param int $id ID de la question
     * @return array|null La question ou null
     */
    public function obtenirParId(int $id): ?array {
        try {
            $stmt = $this->bdd->prepare('SELECT * FROM faq WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Erreur ModeleFAQ::obtenirParId - ' . $e->getMessage());
            return null;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CRÉATION
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Crée une nouvelle question
     * 
     * @param string $question La question
     * @param string $reponse La réponse (HTML)
     * @param string $statut Statut (brouillon/publie)
     * @return int|false L'ID créé ou false
     */
    public function creer(string $question, string $reponse, string $statut = 'publie'): int|false {
        try {
            // Obtenir le prochain ordre
            $stmt = $this->bdd->query('SELECT COALESCE(MAX(ordre), 0) + 1 as prochain FROM faq');
            $prochainOrdre = (int)$stmt->fetch(PDO::FETCH_ASSOC)['prochain'];
            
            $sql = 'INSERT INTO faq (question, reponse, ordre, statut) VALUES (:question, :reponse, :ordre, :statut)';
            $stmt = $this->bdd->prepare($sql);
            $stmt->execute([
                'question' => $question,
                'reponse' => $reponse,
                'ordre' => $prochainOrdre,
                'statut' => $statut
            ]);
            
            return (int)$this->bdd->lastInsertId();
        } catch (PDOException $e) {
            error_log('Erreur ModeleFAQ::creer - ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MODIFICATION
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Modifie une question existante
     * 
     * @param int $id ID de la question
     * @param array $donnees Données à modifier
     * @return bool Succès ou échec
     */
    public function modifier(int $id, array $donnees): bool {
        try {
            $champs = [];
            $params = ['id' => $id];
            
            if (isset($donnees['question'])) {
                $champs[] = 'question = :question';
                $params['question'] = $donnees['question'];
            }
            if (isset($donnees['reponse'])) {
                $champs[] = 'reponse = :reponse';
                $params['reponse'] = $donnees['reponse'];
            }
            if (isset($donnees['statut'])) {
                $champs[] = 'statut = :statut';
                $params['statut'] = $donnees['statut'];
            }
            if (isset($donnees['ordre'])) {
                $champs[] = 'ordre = :ordre';
                $params['ordre'] = $donnees['ordre'];
            }
            
            if (empty($champs)) return false;
            
            $sql = 'UPDATE faq SET ' . implode(', ', $champs) . ' WHERE id = :id';
            return $this->bdd->prepare($sql)->execute($params);
        } catch (PDOException $e) {
            error_log('Erreur ModeleFAQ::modifier - ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SUPPRESSION
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Supprime une question
     * 
     * @param int $id ID de la question
     * @return bool Succès ou échec
     */
    public function supprimer(int $id): bool {
        try {
            // Récupérer l'ordre actuel
            $stmt = $this->bdd->prepare('SELECT ordre FROM faq WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $faq = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$faq) return false;
            
            $ordreActuel = (int)$faq['ordre'];
            
            // Supprimer la question
            $this->bdd->prepare('DELETE FROM faq WHERE id = :id')->execute(['id' => $id]);
            
            // Réorganiser les ordres
            $this->bdd->prepare('UPDATE faq SET ordre = ordre - 1 WHERE ordre > :ordre')
                      ->execute(['ordre' => $ordreActuel]);
            
            return true;
        } catch (PDOException $e) {
            error_log('Erreur ModeleFAQ::supprimer - ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RÉORDONNANCEMENT
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Déplace une question (monter/descendre)
     * 
     * @param int $id ID de la question
     * @param string $direction 'monter' ou 'descendre'
     * @return bool|string True si réussi, 'extreme' si déjà en haut/bas, false si erreur
     */
    public function deplacer(int $id, string $direction): bool|string {
        try {
            // Récupérer la question actuelle
            $stmt = $this->bdd->prepare('SELECT id, ordre FROM faq WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $faq = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$faq) return false;
            
            $ordreActuel = (int)$faq['ordre'];
            
            // Trouver la question adjacente
            if ($direction === 'monter') {
                $stmt = $this->bdd->prepare('SELECT id, ordre FROM faq WHERE ordre < :ordre ORDER BY ordre DESC LIMIT 1');
            } else {
                $stmt = $this->bdd->prepare('SELECT id, ordre FROM faq WHERE ordre > :ordre ORDER BY ordre ASC LIMIT 1');
            }
            $stmt->execute(['ordre' => $ordreActuel]);
            $autreQuestion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pas de question adjacente = position extrême
            if (!$autreQuestion) return 'extreme';
            
            $autreOrdre = (int)$autreQuestion['ordre'];
            
            // Échanger les ordres
            $this->bdd->beginTransaction();
            
            $this->bdd->prepare('UPDATE faq SET ordre = 999999 WHERE id = :id')
                      ->execute(['id' => $id]);
            
            $this->bdd->prepare('UPDATE faq SET ordre = :ordre WHERE id = :id')
                      ->execute(['ordre' => $ordreActuel, 'id' => $autreQuestion['id']]);
            
            $this->bdd->prepare('UPDATE faq SET ordre = :ordre WHERE id = :id')
                      ->execute(['ordre' => $autreOrdre, 'id' => $id]);
            
            $this->bdd->commit();
            return true;
        } catch (PDOException $e) {
            $this->bdd->rollBack();
            error_log('Erreur ModeleFAQ::deplacer - ' . $e->getMessage());
            return false;
        }
    }
}
