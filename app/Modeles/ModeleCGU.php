<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE CGU - GESTION DES CONDITIONS GÉNÉRALES D'UTILISATION
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Structure hiérarchique à 3 niveaux :
 * - Articles (niveau 1) : 1, 2, 3... → titre + statut
 * - Sections (niveau 2) : 1.1, 1.2... → titre + contenu OU points
 * - Points (niveau 3) : 1.1.1, 1.1.2... → contenu uniquement
 * 
 * Tables : cgu_articles, cgu_sections, cgu_points
 */
class ModeleCGU {
    
    private PDO $bdd;

    public function __construct() {
        $this->bdd = BaseDeDonnees::obtenirConnexion();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ARTICLES (NIVEAU 1)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Récupère tous les articles avec leurs sections et points
     */
    public function obtenirTousArticles(bool $uniquementPublies = true): array {
        try {
            $sql = 'SELECT * FROM cgu_articles';
            if ($uniquementPublies) {
                $sql .= ' WHERE statut = :statut';
            }
            $sql .= ' ORDER BY numero ASC';
            
            $stmt = $this->bdd->prepare($sql);
            if ($uniquementPublies) {
                $stmt->execute(['statut' => 'publie']);
            } else {
                $stmt->execute();
            }
            
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Charger les sections pour chaque article
            foreach ($articles as &$article) {
                $article['sections'] = $this->obtenirSectionsParArticle((int)$article['id']);
            }
            
            return $articles;
            
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirTousArticles - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un article par son ID
     */
    public function obtenirArticleParId(int $id): ?array {
        try {
            $stmt = $this->bdd->prepare('SELECT * FROM cgu_articles WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($article) {
                $article['sections'] = $this->obtenirSectionsParArticle((int)$article['id']);
            }
            
            return $article ?: null;
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirArticleParId - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée un nouvel article
     */
    public function creerArticle(string $titre, string $statut = 'publie'): int|false {
        try {
            // Obtenir le prochain numéro
            $stmt = $this->bdd->query('SELECT COALESCE(MAX(numero), 0) + 1 as prochain FROM cgu_articles');
            $prochain = (int)$stmt->fetch(PDO::FETCH_ASSOC)['prochain'];
            
            $sql = 'INSERT INTO cgu_articles (numero, titre, statut) VALUES (:numero, :titre, :statut)';
            $stmt = $this->bdd->prepare($sql);
            $stmt->execute([
                'numero' => $prochain,
                'titre' => $titre,
                'statut' => $statut
            ]);
            
            return (int)$this->bdd->lastInsertId();
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::creerArticle - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifie un article
     */
    public function modifierArticle(int $id, array $donnees): bool {
        try {
            $champs = [];
            $params = ['id' => $id];
            
            if (isset($donnees['titre'])) {
                $champs[] = 'titre = :titre';
                $params['titre'] = $donnees['titre'];
            }
            if (isset($donnees['statut'])) {
                $champs[] = 'statut = :statut';
                $params['statut'] = $donnees['statut'];
            }
            if (isset($donnees['numero'])) {
                $champs[] = 'numero = :numero';
                $params['numero'] = $donnees['numero'];
            }
            
            if (empty($champs)) return false;
            
            $sql = 'UPDATE cgu_articles SET ' . implode(', ', $champs) . ' WHERE id = :id';
            return $this->bdd->prepare($sql)->execute($params);
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::modifierArticle - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un article (cascade sur sections et points)
     */
    public function supprimerArticle(int $id): bool {
        try {
            // Récupérer le numéro de l'article supprimé
            $stmt = $this->bdd->prepare('SELECT numero FROM cgu_articles WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$article) return false;
            
            // Supprimer l'article
            $this->bdd->prepare('DELETE FROM cgu_articles WHERE id = :id')->execute(['id' => $id]);
            
            // Réordonner les articles restants
            $this->bdd->prepare('UPDATE cgu_articles SET numero = numero - 1 WHERE numero > :numero')
                      ->execute(['numero' => $article['numero']]);
            
            return true;
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::supprimerArticle - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Réordonne les articles
     */
    public function reordonnerArticles(array $ordre): bool {
        try {
            $this->bdd->beginTransaction();
            
            // Mettre des numéros temporaires négatifs pour éviter les conflits d'unicité
            foreach ($ordre as $position => $id) {
                $stmt = $this->bdd->prepare('UPDATE cgu_articles SET numero = :numero WHERE id = :id');
                $stmt->execute(['numero' => -($position + 1), 'id' => $id]);
            }
            
            // Remettre des numéros positifs
            foreach ($ordre as $position => $id) {
                $stmt = $this->bdd->prepare('UPDATE cgu_articles SET numero = :numero WHERE id = :id');
                $stmt->execute(['numero' => $position + 1, 'id' => $id]);
            }
            
            $this->bdd->commit();
            return true;
        } catch (PDOException $e) {
            $this->bdd->rollBack();
            error_log('Erreur ModeleCGU::reordonnerArticles - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Déplace un article vers le haut ou le bas
     * @return bool|string true si déplacé, 'extreme' si déjà en position extrême, false si erreur
     */
    public function deplacerArticle(int $id, string $direction): bool|string {
        try {
            // Récupérer l'article actuel
            $stmt = $this->bdd->prepare('SELECT id, numero FROM cgu_articles WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$article) return false;
            
            $numeroActuel = (int)$article['numero'];
            
            // Chercher l'article adjacent (précédent ou suivant)
            if ($direction === 'monter') {
                $stmt = $this->bdd->prepare('SELECT id, numero FROM cgu_articles WHERE numero < :numero ORDER BY numero DESC LIMIT 1');
            } else {
                $stmt = $this->bdd->prepare('SELECT id, numero FROM cgu_articles WHERE numero > :numero ORDER BY numero ASC LIMIT 1');
            }
            $stmt->execute(['numero' => $numeroActuel]);
            $autreArticle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pas d'article adjacent = position extrême (pas une erreur)
            if (!$autreArticle) return 'extreme';
            
            $autreNumero = (int)$autreArticle['numero'];
            
            // Échanger les numéros
            $this->bdd->beginTransaction();
            
            // Mettre l'article actuel à un numéro temporaire (999999 pour éviter les conflits UNIQUE et les colonnes UNSIGNED)
            $this->bdd->prepare('UPDATE cgu_articles SET numero = 999999 WHERE id = :id')
                      ->execute(['id' => $id]);
            
            // Mettre l'autre article au numéro de l'actuel
            $this->bdd->prepare('UPDATE cgu_articles SET numero = :numero WHERE id = :id')
                      ->execute(['numero' => $numeroActuel, 'id' => $autreArticle['id']]);
            
            // Mettre l'article actuel au nouveau numéro
            $this->bdd->prepare('UPDATE cgu_articles SET numero = :numero WHERE id = :id')
                      ->execute(['numero' => $autreNumero, 'id' => $id]);
            
            $this->bdd->commit();
            return true;
        } catch (PDOException $e) {
            $this->bdd->rollBack();
            error_log('Erreur ModeleCGU::deplacerArticle - ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SECTIONS (NIVEAU 2)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Récupère les sections d'un article avec leurs points
     */
    public function obtenirSectionsParArticle(int $articleId): array {
        try {
            $stmt = $this->bdd->prepare('SELECT * FROM cgu_sections WHERE article_id = :article_id ORDER BY numero ASC');
            $stmt->execute(['article_id' => $articleId]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Charger les points pour chaque section
            foreach ($sections as &$section) {
                $section['points'] = $this->obtenirPointsParSection((int)$section['id']);
            }
            
            return $sections;
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirSectionsParArticle - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère une section par son ID
     */
    public function obtenirSectionParId(int $id): ?array {
        try {
            $stmt = $this->bdd->prepare('SELECT * FROM cgu_sections WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($section) {
                $section['points'] = $this->obtenirPointsParSection((int)$section['id']);
            }
            
            return $section ?: null;
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirSectionParId - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée une nouvelle section
     */
    public function creerSection(int $articleId, string $titre, ?string $contenu = null): int|false {
        try {
            // Obtenir le prochain numéro pour cet article
            $stmt = $this->bdd->prepare('SELECT COALESCE(MAX(numero), 0) + 1 as prochain FROM cgu_sections WHERE article_id = :article_id');
            $stmt->execute(['article_id' => $articleId]);
            $prochain = (int)$stmt->fetch(PDO::FETCH_ASSOC)['prochain'];
            
            $sql = 'INSERT INTO cgu_sections (article_id, numero, titre, contenu) VALUES (:article_id, :numero, :titre, :contenu)';
            $stmt = $this->bdd->prepare($sql);
            $stmt->execute([
                'article_id' => $articleId,
                'numero' => $prochain,
                'titre' => $titre,
                'contenu' => $contenu
            ]);
            
            return (int)$this->bdd->lastInsertId();
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::creerSection - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifie une section
     */
    public function modifierSection(int $id, array $donnees): bool {
        try {
            $champs = [];
            $params = ['id' => $id];
            
            if (isset($donnees['titre'])) {
                $champs[] = 'titre = :titre';
                $params['titre'] = $donnees['titre'];
            }
            if (array_key_exists('contenu', $donnees)) {
                $champs[] = 'contenu = :contenu';
                $params['contenu'] = $donnees['contenu'];
            }
            if (isset($donnees['numero'])) {
                $champs[] = 'numero = :numero';
                $params['numero'] = $donnees['numero'];
            }
            
            if (empty($champs)) return false;
            
            $sql = 'UPDATE cgu_sections SET ' . implode(', ', $champs) . ' WHERE id = :id';
            return $this->bdd->prepare($sql)->execute($params);
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::modifierSection - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une section
     */
    public function supprimerSection(int $id): bool {
        try {
            // Récupérer les infos de la section
            $stmt = $this->bdd->prepare('SELECT article_id, numero FROM cgu_sections WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$section) return false;
            
            // Supprimer la section
            $this->bdd->prepare('DELETE FROM cgu_sections WHERE id = :id')->execute(['id' => $id]);
            
            // Réordonner les sections restantes
            $this->bdd->prepare('UPDATE cgu_sections SET numero = numero - 1 WHERE article_id = :article_id AND numero > :numero')
                      ->execute(['article_id' => $section['article_id'], 'numero' => $section['numero']]);
            
            return true;
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::supprimerSection - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Réordonne les sections d'un article
     */
    public function reordonnerSections(int $articleId, array $ordre): bool {
        try {
            $this->bdd->beginTransaction();
            
            foreach ($ordre as $position => $id) {
                $stmt = $this->bdd->prepare('UPDATE cgu_sections SET numero = :numero WHERE id = :id AND article_id = :article_id');
                $stmt->execute(['numero' => -($position + 1), 'id' => $id, 'article_id' => $articleId]);
            }
            
            foreach ($ordre as $position => $id) {
                $stmt = $this->bdd->prepare('UPDATE cgu_sections SET numero = :numero WHERE id = :id AND article_id = :article_id');
                $stmt->execute(['numero' => $position + 1, 'id' => $id, 'article_id' => $articleId]);
            }
            
            $this->bdd->commit();
            return true;
        } catch (PDOException $e) {
            $this->bdd->rollBack();
            error_log('Erreur ModeleCGU::reordonnerSections - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Déplace une section vers le haut ou le bas
     * @return bool|string true si déplacé, 'extreme' si déjà en position extrême, false si erreur
     */
    public function deplacerSection(int $id, string $direction): bool|string {
        try {
            // Récupérer la section actuelle
            $stmt = $this->bdd->prepare('SELECT id, article_id, numero FROM cgu_sections WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$section) return false;
            
            $articleId = (int)$section['article_id'];
            $numeroActuel = (int)$section['numero'];
            
            // Chercher la section adjacente (précédente ou suivante)
            if ($direction === 'monter') {
                $stmt = $this->bdd->prepare('SELECT id, numero FROM cgu_sections WHERE article_id = :article_id AND numero < :numero ORDER BY numero DESC LIMIT 1');
            } else {
                $stmt = $this->bdd->prepare('SELECT id, numero FROM cgu_sections WHERE article_id = :article_id AND numero > :numero ORDER BY numero ASC LIMIT 1');
            }
            $stmt->execute(['article_id' => $articleId, 'numero' => $numeroActuel]);
            $autreSection = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pas de section adjacente = position extrême (pas une erreur)
            if (!$autreSection) return 'extreme';
            
            $autreNumero = (int)$autreSection['numero'];
            
            // Échanger les numéros
            $this->bdd->beginTransaction();
            
            $this->bdd->prepare('UPDATE cgu_sections SET numero = 999999 WHERE id = :id')
                      ->execute(['id' => $id]);
            
            $this->bdd->prepare('UPDATE cgu_sections SET numero = :numero WHERE id = :id')
                      ->execute(['numero' => $numeroActuel, 'id' => $autreSection['id']]);
            
            $this->bdd->prepare('UPDATE cgu_sections SET numero = :numero WHERE id = :id')
                      ->execute(['numero' => $autreNumero, 'id' => $id]);
            
            $this->bdd->commit();
            return true;
        } catch (PDOException $e) {
            $this->bdd->rollBack();
            error_log('Erreur ModeleCGU::deplacerSection - ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // POINTS (NIVEAU 3)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Récupère les points d'une section
     */
    public function obtenirPointsParSection(int $sectionId): array {
        try {
            $stmt = $this->bdd->prepare('SELECT * FROM cgu_points WHERE section_id = :section_id ORDER BY numero ASC');
            $stmt->execute(['section_id' => $sectionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirPointsParSection - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un point par son ID
     */
    public function obtenirPointParId(int $id): ?array {
        try {
            $stmt = $this->bdd->prepare('SELECT * FROM cgu_points WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirPointParId - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée un nouveau point
     */
    public function creerPoint(int $sectionId, string $contenu, ?string $titre = null): int|false {
        try {
            // Obtenir le prochain numéro pour cette section
            $stmt = $this->bdd->prepare('SELECT COALESCE(MAX(numero), 0) + 1 as prochain FROM cgu_points WHERE section_id = :section_id');
            $stmt->execute(['section_id' => $sectionId]);
            $prochain = (int)$stmt->fetch(PDO::FETCH_ASSOC)['prochain'];
            
            // Vider le contenu de la section parente si elle en avait un
            $this->bdd->prepare('UPDATE cgu_sections SET contenu = NULL WHERE id = :id')->execute(['id' => $sectionId]);
            
            $sql = 'INSERT INTO cgu_points (section_id, numero, titre, contenu) VALUES (:section_id, :numero, :titre, :contenu)';
            $stmt = $this->bdd->prepare($sql);
            $stmt->execute([
                'section_id' => $sectionId,
                'numero' => $prochain,
                'titre' => $titre,
                'contenu' => $contenu
            ]);
            
            return (int)$this->bdd->lastInsertId();
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::creerPoint - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifie un point
     */
    public function modifierPoint(int $id, array $donnees): bool {
        try {
            $champs = [];
            $params = ['id' => $id];
            
            if (isset($donnees['contenu'])) {
                $champs[] = 'contenu = :contenu';
                $params['contenu'] = $donnees['contenu'];
            }
            if (isset($donnees['titre'])) {
                $champs[] = 'titre = :titre';
                $params['titre'] = $donnees['titre'] ?: null;
            }
            if (isset($donnees['numero'])) {
                $champs[] = 'numero = :numero';
                $params['numero'] = $donnees['numero'];
            }
            
            if (empty($champs)) return false;
            
            $sql = 'UPDATE cgu_points SET ' . implode(', ', $champs) . ' WHERE id = :id';
            return $this->bdd->prepare($sql)->execute($params);
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::modifierPoint - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un point
     */
    public function supprimerPoint(int $id): bool {
        try {
            // Récupérer les infos du point
            $stmt = $this->bdd->prepare('SELECT section_id, numero FROM cgu_points WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $point = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$point) return false;
            
            // Supprimer le point
            $this->bdd->prepare('DELETE FROM cgu_points WHERE id = :id')->execute(['id' => $id]);
            
            // Réordonner les points restants
            $this->bdd->prepare('UPDATE cgu_points SET numero = numero - 1 WHERE section_id = :section_id AND numero > :numero')
                      ->execute(['section_id' => $point['section_id'], 'numero' => $point['numero']]);
            
            return true;
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::supprimerPoint - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Réordonne les points d'une section
     */
    public function reordonnerPoints(int $sectionId, array $ordre): bool {
        try {
            $this->bdd->beginTransaction();
            
            foreach ($ordre as $position => $id) {
                $stmt = $this->bdd->prepare('UPDATE cgu_points SET numero = :numero WHERE id = :id AND section_id = :section_id');
                $stmt->execute(['numero' => -($position + 1), 'id' => $id, 'section_id' => $sectionId]);
            }
            
            foreach ($ordre as $position => $id) {
                $stmt = $this->bdd->prepare('UPDATE cgu_points SET numero = :numero WHERE id = :id AND section_id = :section_id');
                $stmt->execute(['numero' => $position + 1, 'id' => $id, 'section_id' => $sectionId]);
            }
            
            $this->bdd->commit();
            return true;
        } catch (PDOException $e) {
            $this->bdd->rollBack();
            error_log('Erreur ModeleCGU::reordonnerPoints - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Déplace un point vers le haut ou le bas
     * @return bool|string true si déplacé, 'extreme' si déjà en position extrême, false si erreur
     */
    public function deplacerPoint(int $id, string $direction): bool|string {
        try {
            // Récupérer le point actuel
            $stmt = $this->bdd->prepare('SELECT id, section_id, numero FROM cgu_points WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $point = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$point) return false;
            
            $sectionId = (int)$point['section_id'];
            $numeroActuel = (int)$point['numero'];
            
            // Chercher le point adjacent (précédent ou suivant)
            if ($direction === 'monter') {
                $stmt = $this->bdd->prepare('SELECT id, numero FROM cgu_points WHERE section_id = :section_id AND numero < :numero ORDER BY numero DESC LIMIT 1');
            } else {
                $stmt = $this->bdd->prepare('SELECT id, numero FROM cgu_points WHERE section_id = :section_id AND numero > :numero ORDER BY numero ASC LIMIT 1');
            }
            $stmt->execute(['section_id' => $sectionId, 'numero' => $numeroActuel]);
            $autrePoint = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pas de point adjacent = position extrême (pas une erreur)
            if (!$autrePoint) return 'extreme';
            
            $autreNumero = (int)$autrePoint['numero'];
            
            // Échanger les numéros
            $this->bdd->beginTransaction();
            
            $this->bdd->prepare('UPDATE cgu_points SET numero = 999999 WHERE id = :id')
                      ->execute(['id' => $id]);
            
            $this->bdd->prepare('UPDATE cgu_points SET numero = :numero WHERE id = :id')
                      ->execute(['numero' => $numeroActuel, 'id' => $autrePoint['id']]);
            
            $this->bdd->prepare('UPDATE cgu_points SET numero = :numero WHERE id = :id')
                      ->execute(['numero' => $autreNumero, 'id' => $id]);
            
            $this->bdd->commit();
            return true;
        } catch (PDOException $e) {
            $this->bdd->rollBack();
            error_log('Erreur ModeleCGU::deplacerPoint - ' . $e->getMessage());
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UTILITAIRES
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Récupère la date de dernière mise à jour
     */
    public function obtenirDateDerniereMaj(): ?string {
        try {
            $sql = "SELECT GREATEST(
                        COALESCE((SELECT MAX(updated_at) FROM cgu_articles WHERE statut = 'publie'), '1970-01-01'),
                        COALESCE((SELECT MAX(s.updated_at) FROM cgu_sections s 
                                  INNER JOIN cgu_articles a ON s.article_id = a.id 
                                  WHERE a.statut = 'publie'), '1970-01-01'),
                        COALESCE((SELECT MAX(p.updated_at) FROM cgu_points p 
                                  INNER JOIN cgu_sections s ON p.section_id = s.id 
                                  INNER JOIN cgu_articles a ON s.article_id = a.id 
                                  WHERE a.statut = 'publie'), '1970-01-01')
                    ) as derniere_maj";
            $stmt = $this->bdd->query($sql);
            $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $resultat['derniere_maj'] !== '1970-01-01' ? $resultat['derniere_maj'] : null;
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirDateDerniereMaj - ' . $e->getMessage());
            return null;
        }
    }
}
