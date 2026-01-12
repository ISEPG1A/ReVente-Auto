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
    public function obtenirResume() {
        return [
            'utilisateurs' => $this->obtenirStatsUtilisateurs(),
            'vehicules' => $this->obtenirStatsVehicules(),
            'conversations' => $this->obtenirStatsConversations()
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
            $where .= " AND (u.email LIKE :recherche1 OR u.first_name LIKE :recherche2 OR u.last_name LIKE :recherche3)";
            $rechercheParam = "%$recherche%";
            $params[':recherche1'] = $rechercheParam;
            $params[':recherche2'] = $rechercheParam;
            $params[':recherche3'] = $rechercheParam;
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
            case 'banned':
                $where .= " AND u.banned_at IS NOT NULL";
                break;
        }
        
        $req = $this->db->prepare("
            SELECT 
                u.id, u.first_name, u.last_name, u.email, u.phone,
                u.avatar_path, u.role, u.email_verified_at, u.created_at,
                u.banned_at, u.ban_reason,
                (SELECT COUNT(*) FROM vehicles WHERE user_id = u.id) as nb_annonces,
                (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) as nb_favoris
            FROM users u
            WHERE $where
            ORDER BY u.created_at DESC
            LIMIT :limite OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $req->bindValue($key, $value, PDO::PARAM_STR);
        }
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->bindValue(':offset', $offset, PDO::PARAM_INT);
        $req->execute();
        
        $utilisateurs = $req->fetchAll();
        
        $reqTotal = $this->db->prepare("SELECT COUNT(*) as total FROM users u WHERE $where");
        foreach ($params as $key => $value) {
            $reqTotal->bindValue($key, $value, PDO::PARAM_STR);
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
                (SELECT COUNT(*) FROM vehicles WHERE user_id = u.id) as nb_annonces,
                (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) as nb_favoris,
                (SELECT COUNT(*) FROM conversations WHERE buyer_id = u.id) as nb_conversations_acheteur,
                (SELECT COUNT(*) FROM conversations WHERE seller_id = u.id) as nb_conversations_vendeur
            FROM users u
            WHERE u.id = :id
        ");
        $req->execute([':id' => $id]);
        return $req->fetch();
    }
    
    /**
     * Supprimer un utilisateur (avec log permanent)
     */
    public function supprimerUtilisateur($id, $adminId = null) {
        // Récupérer les infos avant suppression pour le log
        $user = $this->obtenirUtilisateur($id);
        if (!$user) return false;
        
        try {
            $this->db->beginTransaction();
            
            // Logger la suppression avant de l'exécuter
            $this->ajouterLog(
                'suppression_compte',
                'Compte utilisateur supprimé',
                [
                    'email' => $user['email'],
                    'prenom' => $user['first_name'],
                    'nom' => $user['last_name'],
                    'nb_annonces' => $user['nb_annonces'] ?? 0,
                    'inscription' => $user['created_at']
                ],
                $id,
                null,
                $adminId ?? $_SESSION['user']['id'] ?? null
            );
            
            // 1. Supprimer les favoris de l'utilisateur
            $this->db->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$id]);
            
            // 2. Récupérer les véhicules de l'utilisateur pour nettoyage
            $stmtVehicles = $this->db->prepare("SELECT id FROM vehicles WHERE user_id = ?");
            $stmtVehicles->execute([$id]);
            $vehicleIds = $stmtVehicles->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($vehicleIds)) {
                $placeholders = implode(',', array_fill(0, count($vehicleIds), '?'));
                
                // Supprimer les images des véhicules (table peut ne pas exister)
                try {
                    $this->db->prepare("DELETE FROM vehicle_images WHERE vehicle_id IN ($placeholders)")->execute($vehicleIds);
                } catch (PDOException $e) { /* Table inexistante, ignorer */ }
                
                // Supprimer les favoris sur ces véhicules
                $this->db->prepare("DELETE FROM favorites WHERE vehicle_id IN ($placeholders)")->execute($vehicleIds);
            }
            
            // 3. Mettre à NULL les sender_id dans offers et messages (FK SET NULL)
            try {
                $this->db->prepare("UPDATE offers SET sender_id = NULL WHERE sender_id = ?")->execute([$id]);
            } catch (PDOException $e) { /* Table inexistante, ignorer */ }
            
            try {
                $this->db->prepare("UPDATE messages SET sender_id = NULL WHERE sender_id = ?")->execute([$id]);
            } catch (PDOException $e) { /* Table inexistante, ignorer */ }
            
            // 4. Récupérer les conversations de l'utilisateur
            try {
                $stmtConv = $this->db->prepare("SELECT id FROM conversations WHERE buyer_id = ? OR seller_id = ?");
                $stmtConv->execute([$id, $id]);
                $convIds = $stmtConv->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($convIds)) {
                    $placeholdersConv = implode(',', array_fill(0, count($convIds), '?'));
                    
                    // Supprimer d'abord les messages de ces conversations
                    try {
                        $this->db->prepare("DELETE FROM messages WHERE conversation_id IN ($placeholdersConv)")->execute($convIds);
                    } catch (PDOException $e) { /* ignorer */ }
                    
                    // Supprimer les offres de ces conversations
                    try {
                        $this->db->prepare("DELETE FROM offers WHERE conversation_id IN ($placeholdersConv)")->execute($convIds);
                    } catch (PDOException $e) { /* ignorer */ }
                    
                    // Supprimer les conversations
                    $this->db->prepare("DELETE FROM conversations WHERE id IN ($placeholdersConv)")->execute($convIds);
                }
            } catch (PDOException $e) { /* Table conversations inexistante, ignorer */ }
            
            // 5. Supprimer les véhicules
            $this->db->prepare("DELETE FROM vehicles WHERE user_id = ?")->execute([$id]);
            
            // 6. Supprimer les tokens liés au compte (certaines tables peuvent ne pas exister)
            try {
                $this->db->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$id]);
            } catch (PDOException $e) { /* ignorer */ }
            
            try {
                $this->db->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$id]);
            } catch (PDOException $e) { /* ignorer */ }
            
            try {
                $this->db->prepare("DELETE FROM changements_email WHERE id_utilisateur = ?")->execute([$id]);
            } catch (PDOException $e) { /* ignorer */ }
            
            // 7. Enfin supprimer l'utilisateur
            $result = $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur suppression utilisateur $id: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Liste paginée des véhicules
     */
    public function obtenirVehicules($page = 1, $limite = 20, $recherche = '', $filtre = 'all') {
        $offset = ($page - 1) * $limite;
        
        $where = "1=1";
        $params = [];
        
        if (!empty($recherche)) {
            $where .= " AND (v.marque LIKE :recherche1 OR v.modele LIKE :recherche2)";
            $rechercheParam = "%$recherche%";
            $params[':recherche1'] = $rechercheParam;
            $params[':recherche2'] = $rechercheParam;
        }
        
        switch ($filtre) {
            case 'public':
                $where .= " AND v.status = 'public'";
                break;
            case 'prive':
                $where .= " AND v.status = 'prive'";
                break;
            case 'en_attente':
                $where .= " AND v.status = 'en_attente'";
                break;
            case 'refuse':
                $where .= " AND v.status = 'refuse'";
                break;
        }
        
        $req = $this->db->prepare("
            SELECT 
                v.*, 
                u.first_name, u.last_name, u.email,
                (SELECT COUNT(*) FROM favorites WHERE vehicle_id = v.id) as nb_favoris
            FROM vehicles v
            LEFT JOIN users u ON v.user_id = u.id
            WHERE $where
            ORDER BY v.created_at DESC
            LIMIT :limite OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $req->bindValue($key, $value, PDO::PARAM_STR);
        }
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->bindValue(':offset', $offset, PDO::PARAM_INT);
        $req->execute();
        
        $vehicules = $req->fetchAll();
        
        $reqTotal = $this->db->prepare("SELECT COUNT(*) as total FROM vehicles v WHERE $where");
        foreach ($params as $key => $value) {
            $reqTotal->bindValue($key, $value, PDO::PARAM_STR);
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
     * Supprimer un véhicule (avec log permanent)
     */
    public function supprimerVehicule($id, $adminId = null) {
        // Récupérer les infos avant suppression pour le log
        $vehicule = $this->obtenirVehiculeParId($id);
        if (!$vehicule) return false;
        
        // Logger la suppression avant de l'exécuter
        $this->ajouterLog(
            'suppression',
            'Annonce supprimée',
            [
                'marque' => $vehicule['marque'],
                'modele' => $vehicule['modele'],
                'prix' => $vehicule['prix'],
                'annee' => $vehicule['annee'],
                'vendeur' => ($vehicule['first_name'] ?? '') . ' ' . ($vehicule['last_name'] ?? ''),
                'vendeur_email' => $vehicule['email'] ?? ''
            ],
            $vehicule['user_id'] ?? null,
            $id,
            $adminId ?? $_SESSION['user']['id'] ?? null
        );
        
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
    
    // ═══════════════════════════════════════════════════════════════════════════
    // LOGS D'ACTIVITÉ PERMANENTS
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * Ajoute un log d'activité permanent
     */
    public function ajouterLog($type, $action, $details = [], $userId = null, $vehicleId = null, $adminId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (type, action, details, user_id, vehicle_id, admin_id, ip_address)
            VALUES (:type, :action, :details, :user_id, :vehicle_id, :admin_id, :ip)
        ");
        return $stmt->execute([
            ':type' => $type,
            ':action' => $action,
            ':details' => json_encode($details, JSON_UNESCAPED_UNICODE),
            ':user_id' => $userId,
            ':vehicle_id' => $vehicleId,
            ':admin_id' => $adminId,
            ':ip' => Utilitaires::obtenirIpClient()
        ]);
    }
    
    /**
     * Obtient l'activité récente depuis admin_logs avec pagination
     */
    public function obtenirActiviteRecenteLogs($page = 1, $limite = 20, $dateDebut = null, $dateFin = null) {
        $offset = ($page - 1) * $limite;
        $where = "1=1";
        $params = [];
        
        if ($dateDebut) {
            $where .= " AND DATE(l.created_at) >= :dateDebut";
            $params[':dateDebut'] = $dateDebut;
        }
        if ($dateFin) {
            $where .= " AND DATE(l.created_at) <= :dateFin";
            $params[':dateFin'] = $dateFin;
        }
        
        $sql = "
            SELECT l.id, l.type, l.action, l.details, l.user_id, l.vehicle_id, l.admin_id, l.ip_address, l.created_at as date,
                   a.first_name as admin_prenom, a.last_name as admin_nom
            FROM admin_logs l
            LEFT JOIN users a ON l.admin_id = a.id
            WHERE $where
            ORDER BY l.created_at DESC
            LIMIT :limite OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = $stmt->fetchAll();
        
        // Décoder le JSON des détails et ajouter le nom de l'admin
        foreach ($logs as &$log) {
            $log['details'] = json_decode($log['details'], true) ?? [];
            if ($log['admin_prenom'] || $log['admin_nom']) {
                $log['admin_nom_complet'] = trim($log['admin_prenom'] . ' ' . $log['admin_nom']);
            } else {
                $log['admin_nom_complet'] = null;
            }
        }
        
        // Compter le total
        $sqlTotal = "SELECT COUNT(*) FROM admin_logs l WHERE $where";
        $stmtTotal = $this->db->prepare($sqlTotal);
        foreach ($params as $key => $value) {
            $stmtTotal->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmtTotal->execute();
        $total = $stmtTotal->fetchColumn();
        
        return [
            'logs' => $logs,
            'total' => (int)$total,
            'page' => $page,
            'pages_total' => ceil($total / $limite)
        ];
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // GESTION DES CONTACTS
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * Obtient la liste des contacts
     */
    public function obtenirContacts($page = 1, $limite = 20, $filtre = 'all', $dateDebut = null, $dateFin = null) {
        $offset = ($page - 1) * $limite;
        $where = "1=1";
        $params = [];
        
        switch ($filtre) {
            case 'nouveau':
                $where .= " AND status = 'nouveau'";
                break;
            case 'lu':
                $where .= " AND status = 'lu'";
                break;
            case 'traite':
                $where .= " AND status = 'traite'";
                break;
            case 'archive':
                $where .= " AND status = 'archive'";
                break;
        }
        
        if ($dateDebut) {
            $where .= " AND c.created_at >= :date_debut";
            $params[':date_debut'] = $dateDebut;
        }
        
        if ($dateFin) {
            $where .= " AND c.created_at <= :date_fin";
            $params[':date_fin'] = $dateFin;
        }
        
        $stmt = $this->db->prepare("
            SELECT c.*, u.first_name as user_first_name, u.last_name as user_last_name
            FROM contacts c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE $where
            ORDER BY c.created_at DESC
            LIMIT :limite OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $contacts = $stmt->fetchAll();
        
        // Compter le total
        $stmtTotal = $this->db->prepare("SELECT COUNT(*) FROM contacts c WHERE $where");
        foreach ($params as $key => $value) {
            $stmtTotal->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmtTotal->execute();
        $total = $stmtTotal->fetchColumn();
        
        // Compter les nouveaux
        $nouveaux = $this->db->query("SELECT COUNT(*) FROM contacts WHERE status = 'nouveau'")->fetchColumn();
        
        return [
            'contacts' => $contacts,
            'total' => (int)$total,
            'nouveaux' => (int)$nouveaux,
            'page' => $page,
            'pages_total' => ceil($total / $limite)
        ];
    }
    
    /**
     * Met à jour le statut d'un contact
     */
    public function mettreAJourContact($id, $status, $reponse = null, $adminId = null) {
        $sql = "UPDATE contacts SET status = :status";
        $params = [':status' => $status, ':id' => $id];
        
        if ($reponse !== null) {
            $sql .= ", reponse = :reponse, repondu_par = :admin_id, repondu_le = NOW()";
            $params[':reponse'] = $reponse;
            $params[':admin_id'] = $adminId;
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Obtient un contact par ID
     */
    public function obtenirContactParId($id) {
        $stmt = $this->db->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // MODÉRATION DES ANNONCES
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * Obtient les annonces en attente de modération
     */
    public function obtenirAnnoncesEnAttente($page = 1, $limite = 20) {
        $offset = ($page - 1) * $limite;
        
        $stmt = $this->db->prepare("
            SELECT v.*, u.first_name, u.last_name, u.email, u.phone,
                   (SELECT GROUP_CONCAT(image_path) FROM vehicle_images WHERE vehicle_id = v.id) as images_supplementaires
            FROM vehicles v
            LEFT JOIN users u ON v.user_id = u.id
            WHERE v.status = 'en_attente'
            ORDER BY v.created_at ASC
            LIMIT :limite OFFSET :offset
        ");
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $annonces = $stmt->fetchAll();
        
        // Traiter les images supplémentaires
        foreach ($annonces as &$annonce) {
            $annonce['toutes_images'] = [];
            if ($annonce['image_path']) {
                $annonce['toutes_images'][] = $annonce['image_path'];
            }
            if ($annonce['images_supplementaires']) {
                $annonce['toutes_images'] = array_merge(
                    $annonce['toutes_images'],
                    explode(',', $annonce['images_supplementaires'])
                );
            }
        }
        
        $total = $this->db->query("SELECT COUNT(*) FROM vehicles WHERE status = 'en_attente'")->fetchColumn();
        
        return [
            'annonces' => $annonces,
            'total' => (int)$total,
            'page' => $page,
            'pages_total' => ceil($total / $limite)
        ];
    }
    
    /**
     * Modérer une annonce (approuver ou refuser)
     */
    public function modererAnnonce($id, $decision, $adminId, $raison = null) {
        if (!in_array($decision, ['public', 'refuse'])) {
            return false;
        }
        
        // Mettre à jour le statut et la raison du refus si applicable
        if ($decision === 'refuse' && $raison) {
            $stmt = $this->db->prepare("UPDATE vehicles SET status = :status, raison_refus = :raison WHERE id = :id");
            $success = $stmt->execute([':status' => $decision, ':raison' => $raison, ':id' => $id]);
        } else {
            // Si approuvé, effacer la raison de refus précédente
            $stmt = $this->db->prepare("UPDATE vehicles SET status = :status, raison_refus = NULL WHERE id = :id");
            $success = $stmt->execute([':status' => $decision, ':id' => $id]);
        }
        
        if ($success) {
            // Récupérer les infos du véhicule pour le log et l'email
            $vehicule = $this->obtenirVehiculeParId($id);
            
            $this->ajouterLog(
                'moderation',
                $decision === 'public' ? 'Annonce approuvée' : 'Annonce refusée',
                [
                    'marque' => $vehicule['marque'] ?? '',
                    'modele' => $vehicule['modele'] ?? '',
                    'prix' => $vehicule['prix'] ?? 0,
                    'raison' => $raison
                ],
                $vehicule['user_id'] ?? null,
                $id,
                $adminId
            );
            
            // Envoyer un email au propriétaire de l'annonce
            if (!empty($vehicule['email'])) {
                try {
                    ServiceEmail::envoyerNotificationModeration(
                        $vehicule['email'],
                        $vehicule['first_name'] ?? 'Utilisateur',
                        $vehicule['marque'] ?? '',
                        $vehicule['modele'] ?? '',
                        ($decision === 'public'),
                        $raison
                    );
                } catch (Exception $e) {
                    error_log('Erreur envoi email modération: ' . $e->getMessage());
                }
            }
        }
        
        return $success;
    }
    
    /**
     * Obtient un véhicule par ID avec toutes ses images
     */
    public function obtenirVehiculeParId($id) {
        $stmt = $this->db->prepare("
            SELECT v.*, u.first_name, u.last_name, u.email, u.phone
            FROM vehicles v
            LEFT JOIN users u ON v.user_id = u.id
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Compte les annonces en attente
     */
    public function compterAnnoncesEnAttente() {
        return (int)$this->db->query("SELECT COUNT(*) FROM vehicles WHERE status = 'en_attente'")->fetchColumn();
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // GESTION CONTENU STATIQUE (FAQ, CGU, etc.)
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * Obtient le contenu statique par type
     */
    public function obtenirContenuStatique($type) {
        $stmt = $this->db->prepare("
            SELECT * FROM contenu_statique 
            WHERE type = :type AND actif = 1 
            ORDER BY ordre ASC
        ");
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll();
    }
    
    /**
     * Met à jour ou crée un contenu statique
     */
    public function sauvegarderContenuStatique($id, $type, $titre, $contenu, $ordre, $adminId) {
        if ($id) {
            $stmt = $this->db->prepare("
                UPDATE contenu_statique 
                SET titre = :titre, contenu = :contenu, ordre = :ordre, modifie_par = :admin_id
                WHERE id = :id
            ");
            return $stmt->execute([
                ':id' => $id,
                ':titre' => $titre,
                ':contenu' => $contenu,
                ':ordre' => $ordre,
                ':admin_id' => $adminId
            ]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO contenu_statique (type, titre, contenu, ordre, modifie_par)
                VALUES (:type, :titre, :contenu, :ordre, :admin_id)
            ");
            return $stmt->execute([
                ':type' => $type,
                ':titre' => $titre,
                ':contenu' => $contenu,
                ':ordre' => $ordre,
                ':admin_id' => $adminId
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // GESTION DES BANNISSEMENTS ET RÔLES
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Bannir un utilisateur
     */
    public function bannirUtilisateur($userId, $raison, $adminId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET banned_at = NOW(), ban_reason = :raison, banned_by = :admin_id
            WHERE id = :user_id
        ");
        return $stmt->execute([
            ':user_id' => $userId,
            ':raison' => $raison,
            ':admin_id' => $adminId
        ]);
    }

    /**
     * Débannir un utilisateur
     */
    public function debannirUtilisateur($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET banned_at = NULL, ban_reason = NULL, banned_by = NULL
            WHERE id = :user_id
        ");
        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Changer le rôle d'un utilisateur
     */
    public function changerRole($userId, $nouveauRole) {
        if (!in_array($nouveauRole, ['user', 'admin'])) {
            throw new Exception('Rôle invalide');
        }
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :user_id");
        return $stmt->execute([':role' => $nouveauRole, ':user_id' => $userId]);
    }

    /**
     * Obtenir les informations complètes d'un utilisateur
     */
    public function obtenirUtilisateurComplet($userId) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.avatar_path,
                   u.role, u.email_verified_at, u.created_at, u.last_login_at,
                   u.banned_at, u.ban_reason, u.banned_by,
                   (SELECT COUNT(*) FROM vehicles WHERE user_id = u.id) as nb_annonces,
                   (SELECT COUNT(*) FROM favorites WHERE user_id = u.id) as nb_favoris,
                   (SELECT COUNT(*) FROM conversations WHERE buyer_id = u.id OR seller_id = u.id) as nb_conversations
            FROM users u
            WHERE u.id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Récupérer les annonces de l'utilisateur
            $stmtVehicles = $this->db->prepare("
                SELECT id, marque, modele, annee, prix, status, image_path
                FROM vehicles
                WHERE user_id = :user_id
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $stmtVehicles->execute([':user_id' => $userId]);
            $user['annonces'] = $stmtVehicles->fetchAll();
        }
        
        return $user;
    }
}
