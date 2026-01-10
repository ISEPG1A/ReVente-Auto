<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE ADMIN - Gestion administrative de la plateforme
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ModeleAdmin {
    
    private $db;
    
    public function __construct() {
        $this->db = BaseDeDonnees::obtenirConnexion();
    }
    
    /**
     * Obtient le résumé complet pour le dashboard
     */
    public function obtenirResume($limiteActivite = 20, $dateDebut = null, $dateFin = null) {
        return [
            'utilisateurs' => $this->obtenirStatsUtilisateurs(),
            'vehicules' => $this->obtenirStatsVehicules(),
            'conversations' => $this->obtenirStatsConversations(),
            'activite_recente' => $this->obtenirActiviteRecente($limiteActivite, $dateDebut, $dateFin)
        ];
    }
    
    /**
     * Statistiques utilisateurs
     */
    private function obtenirStatsUtilisateurs() {
        $stats = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verifies,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as nouveaux_7j,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as nouveaux_24h
            FROM users
        ")->fetch();
        
        return [
            'total' => (int)$stats['total'],
            'verifies' => (int)$stats['verifies'],
            'non_verifies' => (int)$stats['total'] - (int)$stats['verifies'],
            'nouveaux_7j' => (int)$stats['nouveaux_7j'],
            'nouveaux_24h' => (int)$stats['nouveaux_24h']
        ];
    }
    
    /**
     * Statistiques véhicules
     */
    private function obtenirStatsVehicules() {
        $stats = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'public' THEN 1 ELSE 0 END) as publics,
                SUM(CASE WHEN status = 'prive' THEN 1 ELSE 0 END) as prives,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as nouveaux_7j,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 ELSE 0 END) as nouveaux_24h,
                ROUND(AVG(prix), 2) as prix_moyen
            FROM vehicles
        ")->fetch();
        
        return [
            'total' => (int)$stats['total'],
            'publics' => (int)$stats['publics'],
            'prives' => (int)$stats['prives'],
            'nouveaux_7j' => (int)$stats['nouveaux_7j'],
            'nouveaux_24h' => (int)$stats['nouveaux_24h'],
            'prix_moyen' => (float)$stats['prix_moyen']
        ];
    }
    
    /**
     * Statistiques conversations
     */
    private function obtenirStatsConversations() {
        $stats = $this->db->query("
            SELECT 
                COUNT(DISTINCT c.id) as total_conversations,
                COUNT(m.id) as total_messages,
                SUM(CASE WHEN c.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as actives_7j
            FROM conversations c
            LEFT JOIN messages m ON c.id = m.conversation_id
        ")->fetch();
        
        return [
            'total_conversations' => (int)$stats['total_conversations'],
            'total_messages' => (int)$stats['total_messages'],
            'actives_7j' => (int)$stats['actives_7j']
        ];
    }
    
    /**
     * Liste paginée des utilisateurs
     */
    public function obtenirUtilisateurs($page = 1, $limite = 20, $recherche = '', $filtre = 'all') {
        $offset = ($page - 1) * $limite;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($recherche)) {
            $where .= " AND (u.email LIKE :recherche OR u.first_name LIKE :recherche OR u.last_name LIKE :recherche)";
            $params[':recherche'] = "%$recherche%";
        }
        
        switch ($filtre) {
            case 'verified':
                $where .= " AND u.email_verified_at IS NOT NULL";
                break;
            case 'unverified':
                $where .= " AND u.email_verified_at IS NULL";
                break;
            case 'admin':
                $where .= " AND u.role = 'admin'";
                break;
        }
        
        $req = $this->db->prepare("
            SELECT 
                u.id, u.first_name, u.last_name, u.email, u.phone,
                u.avatar_path, u.role, u.email_verified_at, u.created_at,
                COUNT(DISTINCT v.id) as nb_annonces,
                COUNT(DISTINCT f.vehicle_id) as nb_favoris
            FROM users u
            LEFT JOIN vehicles v ON u.id = v.user_id
            LEFT JOIN favorites f ON u.id = f.user_id
            WHERE $where
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT :limite OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $req->bindValue($key, $value);
        }
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->bindValue(':offset', $offset, PDO::PARAM_INT);
        $req->execute();
        
        $utilisateurs = $req->fetchAll();
        
        $reqTotal = $this->db->prepare("SELECT COUNT(*) as total FROM users u WHERE $where");
        foreach ($params as $key => $value) {
            $reqTotal->bindValue($key, $value);
        }
        $reqTotal->execute();
        $total = $reqTotal->fetch()['total'];
        
        return [
            'utilisateurs' => $utilisateurs,
            'total' => (int)$total,
            'page' => $page,
            'pages_total' => ceil($total / $limite)
        ];
    }
    
    /**
     * Détails d'un utilisateur
     */
    public function obtenirUtilisateur($id) {
        $req = $this->db->prepare("
            SELECT 
                u.*,
                COUNT(DISTINCT v.id) as nb_annonces,
                COUNT(DISTINCT f.vehicle_id) as nb_favoris,
                COUNT(DISTINCT c1.id) as nb_conversations_acheteur,
                COUNT(DISTINCT c2.id) as nb_conversations_vendeur
            FROM users u
            LEFT JOIN vehicles v ON u.id = v.user_id
            LEFT JOIN favorites f ON u.id = f.user_id
            LEFT JOIN conversations c1 ON u.id = c1.buyer_id
            LEFT JOIN conversations c2 ON u.id = c2.seller_id
            WHERE u.id = :id
            GROUP BY u.id
        ");
        $req->execute([':id' => $id]);
        return $req->fetch();
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function supprimerUtilisateur($id) {
        $req = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $req->execute([':id' => $id]);
    }
    
    /**
     * Liste paginée des véhicules
     */
    public function obtenirVehicules($page = 1, $limite = 20, $recherche = '', $filtre = 'all') {
        $offset = ($page - 1) * $limite;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($recherche)) {
            $where .= " AND (v.marque LIKE :recherche OR v.modele LIKE :recherche)";
            $params[':recherche'] = "%$recherche%";
        }
        
        switch ($filtre) {
            case 'public':
                $where .= " AND v.status = 'public'";
                break;
            case 'prive':
                $where .= " AND v.status = 'prive'";
                break;
        }
        
        $req = $this->db->prepare("
            SELECT 
                v.*, 
                u.first_name, u.last_name, u.email,
                COUNT(DISTINCT f.user_id) as nb_favoris,
                v.views_count, v.contacts_count
            FROM vehicles v
            LEFT JOIN users u ON v.user_id = u.id
            LEFT JOIN favorites f ON v.id = f.vehicle_id
            WHERE $where
            GROUP BY v.id
            ORDER BY v.created_at DESC
            LIMIT :limite OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $req->bindValue($key, $value);
        }
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->bindValue(':offset', $offset, PDO::PARAM_INT);
        $req->execute();
        
        $vehicules = $req->fetchAll();
        
        $reqTotal = $this->db->prepare("SELECT COUNT(*) as total FROM vehicles v WHERE $where");
        foreach ($params as $key => $value) {
            $reqTotal->bindValue($key, $value);
        }
        $reqTotal->execute();
        $total = $reqTotal->fetch()['total'];
        
        return [
            'vehicules' => $vehicules,
            'total' => (int)$total,
            'page' => $page,
            'pages_total' => ceil($total / $limite)
        ];
    }
    
    /**
     * Changer le statut d'un véhicule
     */
    public function changerStatutVehicule($id, $statut) {
        if (!in_array($statut, ['public', 'prive'])) {
            return false;
        }
        
        $req = $this->db->prepare("UPDATE vehicles SET status = :statut WHERE id = :id");
        return $req->execute([':statut' => $statut, ':id' => $id]);
    }
    
    /**
     * Supprimer un véhicule
     */
    public function supprimerVehicule($id) {
        $req = $this->db->prepare("DELETE FROM vehicles WHERE id = :id");
        return $req->execute([':id' => $id]);
    }
    
    /**
     * Obtient l'activité récente de la plateforme
     */
    public function obtenirActiviteRecente($limite = 20, $dateDebut = null, $dateFin = null) {
        $activites = [];
        
        // Préparer les conditions de date
        $conditionDate = "";
        $params = [':limite' => $limite];
        
        if ($dateDebut) {
            $conditionDate .= " AND DATE(created_at) >= :dateDebut";
            $params[':dateDebut'] = $dateDebut;
        }
        if ($dateFin) {
            $conditionDate .= " AND DATE(created_at) <= :dateFin";
            $params[':dateFin'] = $dateFin;
        }
        
        $inscriptions = $this->db->prepare("
            SELECT 'inscription' as type, id, first_name, last_name, email, created_at as date
            FROM users
            WHERE 1=1 " . $conditionDate . "
            ORDER BY created_at DESC
            LIMIT :limite
        ");
        foreach ($params as $key => $value) {
            if ($key !== ':limite') {
                $inscriptions->bindValue($key, $value, PDO::PARAM_STR);
            } else {
                $inscriptions->bindValue($key, $value, PDO::PARAM_INT);
            }
        }
        $inscriptions->execute();
        $activites = array_merge($activites, $inscriptions->fetchAll());
        
        $conditionDateVehicule = str_replace('created_at', 'v.created_at', $conditionDate);
        $annonces = $this->db->prepare("
            SELECT 
                'annonce' as type, 
                v.id, v.marque, v.modele, v.prix, v.created_at as date,
                u.first_name, u.last_name
            FROM vehicles v
            LEFT JOIN users u ON v.user_id = u.id
            WHERE 1=1 " . $conditionDateVehicule . "
            ORDER BY v.created_at DESC
            LIMIT :limite
        ");
        foreach ($params as $key => $value) {
            if ($key !== ':limite') {
                $annonces->bindValue($key, $value, PDO::PARAM_STR);
            } else {
                $annonces->bindValue($key, $value, PDO::PARAM_INT);
            }
        }
        $annonces->execute();
        $activites = array_merge($activites, $annonces->fetchAll());
        
        $conditionDateConv = str_replace('created_at', 'c.updated_at', $conditionDate);
        $conversations = $this->db->prepare("
            SELECT 
                'conversation' as type,
                c.id, c.updated_at as date,
                u1.first_name as acheteur_prenom, u1.last_name as acheteur_nom,
                u2.first_name as vendeur_prenom, u2.last_name as vendeur_nom,
                v.marque, v.modele
            FROM conversations c
            LEFT JOIN users u1 ON c.buyer_id = u1.id
            LEFT JOIN users u2 ON c.seller_id = u2.id
            LEFT JOIN vehicles v ON c.vehicle_id = v.id
            WHERE 1=1 " . $conditionDateConv . "
            ORDER BY c.updated_at DESC
            LIMIT :limite
        ");
        foreach ($params as $key => $value) {
            if ($key !== ':limite') {
                $conversations->bindValue($key, $value, PDO::PARAM_STR);
            } else {
                $conversations->bindValue($key, $value, PDO::PARAM_INT);
            }
        }
        $conversations->execute();
        $activites = array_merge($activites, $conversations->fetchAll());
        
        usort($activites, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return array_slice($activites, 0, $limite);
    }
    
    /**
     * Top marques les plus populaires
     */
    public function obtenirTopMarques($limite = 10) {
        $req = $this->db->prepare("
            SELECT marque, COUNT(*) as nb_annonces
            FROM vehicles
            GROUP BY marque
            ORDER BY nb_annonces DESC
            LIMIT :limite
        ");
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->execute();
        return $req->fetchAll();
    }
    
    /**
     * Répartition par type de véhicule
     */
    public function obtenirRepartitionTypes() {
        return $this->db->query("
            SELECT type_vehicule, COUNT(*) as nb_annonces
            FROM vehicles
            GROUP BY type_vehicule
            ORDER BY nb_annonces DESC
        ")->fetchAll();
    }
    
    /**
     * Évolution des inscriptions (7 derniers jours)
     */
    public function obtenirEvolutionInscriptions() {
        return $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as nb_inscriptions
            FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ")->fetchAll();
    }
    
    /**
     * Évolution des annonces (7 derniers jours)
     */
    public function obtenirEvolutionAnnonces() {
        return $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as nb_annonces
            FROM vehicles
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ")->fetchAll();
    }
}
