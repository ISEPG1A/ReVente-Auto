<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE CGU - GESTION DES ARTICLES CGU EN BASE DE DONNÉES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce modèle gère toutes les opérations CRUD (Create, Read, Update, Delete)
 * pour les articles des Conditions Générales d'Utilisation stockés en base.
 * 
 * Fonctionnalités principales :
 * - Récupération de tous les articles ou d'un article spécifique
 * - Création de nouveaux articles
 * - Modification d'articles existants
 * - Suppression d'articles
 * - Filtrage par statut (brouillon/publié) et visibilité
 * 
 * @author  mat
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ModeleCGU {
    
    private $bdd;

    /**
     * Constructeur : initialise la connexion à la base de données
     * Utilise le singleton BaseDeDonnees pour obtenir la connexion PDO
     */
    public function __construct() {
        $this->bdd = BaseDeDonnees::obtenirConnexion();
    }

    /**
     * Récupère tous les articles CGU
     * 
     * @param bool $uniquementPublies Si true, retourne uniquement les articles publiés et visibles
     *                                 Si false, retourne tous les articles (pour l'admin)
     * @return array Tableau d'articles avec toutes leurs propriétés
     * 
     * Structure de retour : [
     *   ['id' => 1, 'numero_article' => 1, 'titre' => '...', 'contenu' => '...', 
     *    'ordre' => 1, 'statut' => 'publie', 'visible' => 1, ...],
     *   ...
     * ]
     */
    public function obtenirTous(bool $uniquementPublies = true): array {
        try {
            // Construction de la requête SQL
            $sql = 'SELECT * FROM cgu_articles';
            
            // Si on veut uniquement les articles publiés (pour l'affichage public)
            if ($uniquementPublies) {
                $sql .= ' WHERE statut = "publie" AND visible = TRUE';
            }
            
            // ORDER BY trie les articles par leur ordre d'affichage, puis par numéro d'article
            $sql .= ' ORDER BY ordre ASC, numero_article ASC';
            
            // Préparation et exécution de la requête
            $stmt = $this->bdd->prepare($sql);
            $stmt->execute();
            
            // Récupération de tous les résultats sous forme de tableau associatif
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirTous - ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un article CGU par son identifiant
     * 
     * @param int $id Identifiant de l'article à récupérer
     * @return array|null Tableau associatif de l'article ou null si non trouvé
     * 
     * Exemple de retour :
     * ['id' => 1, 'numero_article' => 1, 'titre' => 'Objet...', ...]
     */
    public function obtenirParId(int $id): ?array {
        try {
            // Requête SQL avec paramètre préparé pour éviter les injections SQL
            $sql = 'SELECT * FROM cgu_articles WHERE id = :id';
            $stmt = $this->bdd->prepare($sql);
            
            // Liaison du paramètre :id avec la valeur $id en tant qu'entier
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // fetch() retourne une ligne ou false si aucun résultat
            $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Conversion de false en null pour plus de clarté
            return $resultat ?: null;
            
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirParId - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée un nouvel article CGU dans la base de données
     * 
     * @param array $donnees Tableau contenant les données de l'article
     *                       Clés requises : numero_article, titre, contenu
     *                       Clés optionnelles : ordre, statut, visible
     * @return int|false ID du nouvel article créé ou false en cas d'échec
     * 
     * Exemple d'utilisation :
     * $id = $modele->creer([
     *   'numero_article' => 4,
     *   'titre' => 'Nouveau titre',
     *   'contenu' => '<p>Contenu HTML...</p>',
     *   'ordre' => 4,
     *   'statut' => 'publie'
     * ]);
     */
    public function creer(array $donnees) {
        try {
            // Requête d'insertion avec paramètres préparés
            $sql = 'INSERT INTO cgu_articles 
                    (numero_article, titre, contenu, ordre, statut, visible) 
                    VALUES 
                    (:numero_article, :titre, :contenu, :ordre, :statut, :visible)';
            
            $stmt = $this->bdd->prepare($sql);
            
            // Liaison de tous les paramètres avec valeurs par défaut si non fournies
            $stmt->bindParam(':numero_article', $donnees['numero_article'], PDO::PARAM_INT);
            $stmt->bindParam(':titre', $donnees['titre'], PDO::PARAM_STR);
            $stmt->bindParam(':contenu', $donnees['contenu'], PDO::PARAM_STR);
            
            // Si 'ordre' n'est pas fourni, utilise 0 par défaut
            $ordre = $donnees['ordre'] ?? 0;
            $stmt->bindParam(':ordre', $ordre, PDO::PARAM_INT);
            
            // Si 'statut' n'est pas fourni, utilise 'publie' par défaut
            $statut = $donnees['statut'] ?? 'publie';
            $stmt->bindParam(':statut', $statut, PDO::PARAM_STR);
            
            // Si 'visible' n'est pas fourni, utilise true par défaut
            $visible = $donnees['visible'] ?? true;
            $stmt->bindParam(':visible', $visible, PDO::PARAM_BOOL);
            
            // Exécution de la requête
            $stmt->execute();
            
            // lastInsertId() retourne l'ID auto-incrémenté de la ligne insérée
            return $this->bdd->lastInsertId();
            
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::creer - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifie un article CGU existant
     * 
     * @param int $id Identifiant de l'article à modifier
     * @param array $donnees Tableau des champs à mettre à jour
     *                       Peut contenir : numero_article, titre, contenu, ordre, statut, visible
     * @return bool True si la modification a réussi, false sinon
     * 
     * Note : Seuls les champs présents dans $donnees seront modifiés,
     *        les autres resteront inchangés
     */
    public function modifier(int $id, array $donnees): bool {
        try {
            // Construction dynamique de la clause SET de la requête UPDATE
            // On ne met à jour que les champs fournis dans $donnees
            $champsAModifier = [];
            $params = [':id' => $id];
            
            // Liste des champs autorisés à être modifiés
            $champsAutorises = ['numero_article', 'titre', 'contenu', 'ordre', 'statut', 'visible'];
            
            // Pour chaque champ autorisé présent dans $donnees
            foreach ($champsAutorises as $champ) {
                if (isset($donnees[$champ])) {
                    // Ajout de "champ = :champ" à la clause SET
                    $champsAModifier[] = "$champ = :$champ";
                    // Ajout de la valeur aux paramètres
                    $params[":$champ"] = $donnees[$champ];
                }
            }
            
            // Si aucun champ à modifier, retourne false
            if (empty($champsAModifier)) {
                return false;
            }
            
            // Construction de la requête SQL complète
            $sql = 'UPDATE cgu_articles SET ' . implode(', ', $champsAModifier) . ' WHERE id = :id';
            
            $stmt = $this->bdd->prepare($sql);
            
            // Exécution avec tous les paramètres
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::modifier - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un article CGU de la base de données
     * 
     * @param int $id Identifiant de l'article à supprimer
     * @return bool True si la suppression a réussi, false sinon
     * 
     * Attention : Cette suppression est définitive !
     */
    public function supprimer(int $id): bool {
        try {
            // Requête de suppression simple avec WHERE
            $sql = 'DELETE FROM cgu_articles WHERE id = :id';
            $stmt = $this->bdd->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            // execute() retourne true en cas de succès
            return $stmt->execute();
            
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::supprimer - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le prochain numéro d'article disponible
     * 
     * @return int Le numéro d'article suivant (max + 1)
     * 
     * Utile lors de la création d'un nouvel article pour lui attribuer
     * automatiquement le prochain numéro dans la séquence
     */
    public function obtenirProchainNumero(): int {
        try {
            // MAX() retourne la valeur maximale de la colonne numero_article
            $sql = 'SELECT MAX(numero_article) as max_numero FROM cgu_articles';
            $stmt = $this->bdd->query($sql);
            $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si aucun article n'existe (max_numero est NULL), retourne 1
            // Sinon retourne max + 1
            return ($resultat['max_numero'] ?? 0) + 1;
            
        } catch (PDOException $e) {
            error_log('Erreur ModeleCGU::obtenirProchainNumero - ' . $e->getMessage());
            return 1;
        }
    }
}
