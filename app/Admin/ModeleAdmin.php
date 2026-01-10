<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE ADMIN - Requêtes statistiques et données administratives
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce modèle contient toutes les requêtes SQL pour le dashboard administrateur :
 * - Statistiques utilisateurs (total, vérifiés, non vérifiés)
 * - Statistiques véhicules (par type, par état, récents)
 * - Statistiques conversations et messages
 * - Statistiques offres et favoris
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ModeleAdmin {
    
    private $db;
    
    public function __construct() {
        $this->db = BaseDeDonnees::obtenirConnexion();
    }
    
    // ========================================================================
    // STATISTIQUES UTILISATEURS
    // ========================================================================
    
    /**
     * Obtient les statistiques globales des utilisateurs
     * 
     * @return array Statistiques utilisateurs
     */
    public function obtenirStatistiquesUtilisateurs() {
        // Total utilisateurs
        $reqTotal = $this->db->query("SELECT COUNT(*) as total FROM users");
        $total = $reqTotal->fetch()['total'];
        
        // Utilisateurs avec email vérifié
        $reqVerifies = $this->db->query("SELECT COUNT(*) as total FROM users WHERE email_verified_at IS NOT NULL");
        $verifies = $reqVerifies->fetch()['total'];
        
        // Utilisateurs sans email vérifié
        $nonVerifies = $total - $verifies;
        
        // Administrateurs
        $reqAdmins = $this->db->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
        $admins = $reqAdmins->fetch()['total'];
        
        // Nouveaux utilisateurs (dernières 24h)
        $reqNouveaux24h = $this->db->query("
            SELECT COUNT(*) as total FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $nouveaux24h = $reqNouveaux24h->fetch()['total'];
        
        // Nouveaux utilisateurs (7 derniers jours)
        $reqNouveaux7j = $this->db->query("
            SELECT COUNT(*) as total FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $nouveaux7j = $reqNouveaux7j->fetch()['total'];
        
        // Utilisateurs avec téléphone vérifié
        $reqTelVerifies = $this->db->query("SELECT COUNT(*) as total FROM users WHERE phone_verified_at IS NOT NULL");
        $telVerifies = $reqTelVerifies->fetch()['total'];
        
        return [
            'total' => (int)$total,
            'email_verifies' => (int)$verifies,
            'email_non_verifies' => (int)$nonVerifies,
            'admins' => (int)$admins,
            'nouveaux_24h' => (int)$nouveaux24h,
            'nouveaux_7j' => (int)$nouveaux7j,
            'telephone_verifies' => (int)$telVerifies
        ];
    }
    
    /**
     * Obtient la liste des utilisateurs avec pagination
     * 
     * @param int $page Numéro de la page
     * @param int $parPage Nombre d'éléments par page
     * @param string $filtre Filtre (all, verified, unverified, admin)
     * @return array Liste des utilisateurs
     */
    public function obtenirListeUtilisateurs($page = 1, $parPage = 20, $filtre = 'all') {
        $offset = ($page - 1) * $parPage;
        
        $whereClause = "";
        switch ($filtre) {
            case 'verified':
                $whereClause = "WHERE email_verified_at IS NOT NULL";
                break;
            case 'unverified':
                $whereClause = "WHERE email_verified_at IS NULL";
                break;
            case 'admin':
                $whereClause = "WHERE role = 'admin'";
                break;
        }
        
        $req = $this->db->prepare("
            SELECT 
                id, first_name, last_name, email, phone, role,
                email_verified_at, phone_verified_at, avatar_path,
                created_at, updated_at
            FROM users 
            $whereClause
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $req->bindValue(':limit', $parPage, PDO::PARAM_INT);
        $req->bindValue(':offset', $offset, PDO::PARAM_INT);
        $req->execute();
        
        return $req->fetchAll();
    }
    
    /**
     * Compte le total d'utilisateurs selon le filtre
     * 
     * @param string $filtre Filtre appliqué
     * @return int Nombre total
     */
    public function compterUtilisateurs($filtre = 'all') {
        $whereClause = "";
        switch ($filtre) {
            case 'verified':
                $whereClause = "WHERE email_verified_at IS NOT NULL";
                break;
            case 'unverified':
                $whereClause = "WHERE email_verified_at IS NULL";
                break;
            case 'admin':
                $whereClause = "WHERE role = 'admin'";
                break;
        }
        
        $req = $this->db->query("SELECT COUNT(*) as total FROM users $whereClause");
        return (int)$req->fetch()['total'];
    }
    
    // ========================================================================
    // STATISTIQUES VÉHICULES
    // ========================================================================
    
    /**
     * Obtient les statistiques globales des véhicules
     * 
     * @return array Statistiques véhicules
     */
    public function obtenirStatistiquesVehicules() {
        // Total véhicules
        $reqTotal = $this->db->query("SELECT COUNT(*) as total FROM vehicles");
        $total = $reqTotal->fetch()['total'];
        
        // Par type
        $reqParType = $this->db->query("
            SELECT type_vehicule, COUNT(*) as count 
            FROM vehicles 
            GROUP BY type_vehicule
        ");
        $parType = [];
        while ($row = $reqParType->fetch()) {
            $parType[$row['type_vehicule']] = (int)$row['count'];
        }
        
        // Par état
        $reqParEtat = $this->db->query("
            SELECT etat, COUNT(*) as count 
            FROM vehicles 
            WHERE etat IS NOT NULL
            GROUP BY etat
        ");
        $parEtat = [];
        while ($row = $reqParEtat->fetch()) {
            $parEtat[$row['etat']] = (int)$row['count'];
        }
        
        // Par carburant
        $reqParCarburant = $this->db->query("
            SELECT carburant, COUNT(*) as count 
            FROM vehicles 
            WHERE carburant IS NOT NULL
            GROUP BY carburant
        ");
        $parCarburant = [];
        while ($row = $reqParCarburant->fetch()) {
            $parCarburant[$row['carburant']] = (int)$row['count'];
        }
        
        // Prix moyen
        $reqPrixMoyen = $this->db->query("SELECT AVG(prix) as moyenne FROM vehicles");
        $prixMoyen = round($reqPrixMoyen->fetch()['moyenne'] ?? 0, 2);
        
        // Kilométrage moyen
        $reqKmMoyen = $this->db->query("SELECT AVG(km) as moyenne FROM vehicles WHERE km IS NOT NULL");
        $kmMoyen = round($reqKmMoyen->fetch()['moyenne'] ?? 0, 0);
        
        // Nouveaux véhicules (dernières 24h)
        $reqNouveaux24h = $this->db->query("
            SELECT COUNT(*) as total FROM vehicles 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $nouveaux24h = $reqNouveaux24h->fetch()['total'];
        
        // Nouveaux véhicules (7 derniers jours)
        $reqNouveaux7j = $this->db->query("
            SELECT COUNT(*) as total FROM vehicles 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $nouveaux7j = $reqNouveaux7j->fetch()['total'];
        
        // Véhicules avec score IA
        $reqAvecScore = $this->db->query("SELECT COUNT(*) as total FROM vehicles WHERE score_ia IS NOT NULL");
        $avecScore = $reqAvecScore->fetch()['total'];
        
        // Score IA moyen
        $reqScoreMoyen = $this->db->query("SELECT AVG(score_ia) as moyenne FROM vehicles WHERE score_ia IS NOT NULL");
        $scoreMoyen = round($reqScoreMoyen->fetch()['moyenne'] ?? 0, 1);
        
        return [
            'total' => (int)$total,
            'par_type' => $parType,
            'par_etat' => $parEtat,
            'par_carburant' => $parCarburant,
            'prix_moyen' => $prixMoyen,
            'km_moyen' => (int)$kmMoyen,
            'nouveaux_24h' => (int)$nouveaux24h,
            'nouveaux_7j' => (int)$nouveaux7j,
            'avec_score_ia' => (int)$avecScore,
            'score_ia_moyen' => $scoreMoyen
        ];
    }
    
    /**
     * Obtient les derniers véhicules ajoutés
     * 
     * @param int $limite Nombre de véhicules à récupérer
     * @return array Liste des derniers véhicules
     */
    public function obtenirDerniersVehicules($limite = 10) {
        $req = $this->db->prepare("
            SELECT 
                v.id, v.type_vehicule, v.marque, v.modele, v.annee, v.prix, 
                v.km, v.ville, v.etat, v.score_ia, v.created_at,
                u.first_name, u.last_name, u.email as vendeur_email
            FROM vehicles v
            LEFT JOIN users u ON v.user_id = u.id
            ORDER BY v.created_at DESC
            LIMIT :limite
        ");
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->execute();
        
        return $req->fetchAll();
    }
    
    /**
     * Obtient les marques les plus populaires
     * 
     * @param int $limite Nombre de marques
     * @return array Liste des marques populaires
     */
    public function obtenirMarquesPopulaires($limite = 10) {
        $req = $this->db->prepare("
            SELECT marque, COUNT(*) as count 
            FROM vehicles 
            GROUP BY marque 
            ORDER BY count DESC
            LIMIT :limite
        ");
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->execute();
        
        return $req->fetchAll();
    }
    
    // ========================================================================
    // STATISTIQUES CONVERSATIONS ET MESSAGES
    // ========================================================================
    
    /**
     * Obtient les statistiques des conversations et messages
     * 
     * @return array Statistiques messagerie
     */
    public function obtenirStatistiquesMessagerie() {
        // Total conversations
        $reqConv = $this->db->query("SELECT COUNT(*) as total FROM conversations");
        $totalConv = $reqConv->fetch()['total'];
        
        // Total messages
        $reqMsg = $this->db->query("SELECT COUNT(*) as total FROM messages");
        $totalMsg = $reqMsg->fetch()['total'];
        
        // Messages non lus
        $reqNonLus = $this->db->query("SELECT COUNT(*) as total FROM messages WHERE is_read = 0");
        $nonLus = $reqNonLus->fetch()['total'];
        
        // Conversations actives (avec message dans les 24h)
        $reqActives = $this->db->query("
            SELECT COUNT(DISTINCT conversation_id) as total 
            FROM messages 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $actives24h = $reqActives->fetch()['total'];
        
        // Messages des dernières 24h
        $reqMsg24h = $this->db->query("
            SELECT COUNT(*) as total FROM messages 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $messages24h = $reqMsg24h->fetch()['total'];
        
        // Messages des 7 derniers jours
        $reqMsg7j = $this->db->query("
            SELECT COUNT(*) as total FROM messages 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $messages7j = $reqMsg7j->fetch()['total'];
        
        return [
            'total_conversations' => (int)$totalConv,
            'total_messages' => (int)$totalMsg,
            'messages_non_lus' => (int)$nonLus,
            'conversations_actives_24h' => (int)$actives24h,
            'messages_24h' => (int)$messages24h,
            'messages_7j' => (int)$messages7j
        ];
    }
    
    /**
     * Obtient les derniers messages (flux de conversation)
     * 
     * @param int $limite Nombre de messages
     * @return array Liste des derniers messages
     */
    public function obtenirDerniersMessages($limite = 20) {
        $req = $this->db->prepare("
            SELECT 
                m.id, m.conversation_id, m.is_read, m.created_at,
                u.first_name, u.last_name, u.email as sender_email,
                v.marque, v.modele, v.id as vehicle_id
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.id
            LEFT JOIN conversations c ON m.conversation_id = c.id
            LEFT JOIN vehicles v ON c.vehicle_id = v.id
            ORDER BY m.created_at DESC
            LIMIT :limite
        ");
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->execute();
        
        return $req->fetchAll();
    }
    
    // ========================================================================
    // STATISTIQUES OFFRES
    // ========================================================================
    
    /**
     * Obtient les statistiques des offres
     * 
     * @return array Statistiques offres
     */
    public function obtenirStatistiquesOffres() {
        // Total offres
        $reqTotal = $this->db->query("SELECT COUNT(*) as total FROM offers");
        $total = $reqTotal->fetch()['total'];
        
        // Par statut
        $reqParStatut = $this->db->query("
            SELECT status, COUNT(*) as count 
            FROM offers 
            GROUP BY status
        ");
        $parStatut = [];
        while ($row = $reqParStatut->fetch()) {
            $parStatut[$row['status']] = (int)$row['count'];
        }
        
        // Montant moyen des offres
        $reqMontantMoyen = $this->db->query("SELECT AVG(amount) as moyenne FROM offers");
        $montantMoyen = round($reqMontantMoyen->fetch()['moyenne'] ?? 0, 2);
        
        // Offres des dernières 24h
        $reqOffres24h = $this->db->query("
            SELECT COUNT(*) as total FROM offers 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $offres24h = $reqOffres24h->fetch()['total'];
        
        // Taux d'acceptation
        $accepted = $parStatut['accepted'] ?? 0;
        $declined = $parStatut['declined'] ?? 0;
        $tauxAcceptation = ($accepted + $declined > 0) 
            ? round(($accepted / ($accepted + $declined)) * 100, 1) 
            : 0;
        
        return [
            'total' => (int)$total,
            'par_statut' => $parStatut,
            'montant_moyen' => $montantMoyen,
            'offres_24h' => (int)$offres24h,
            'taux_acceptation' => $tauxAcceptation
        ];
    }
    
    /**
     * Obtient les dernières offres
     * 
     * @param int $limite Nombre d'offres
     * @return array Liste des dernières offres
     */
    public function obtenirDernieresOffres($limite = 10) {
        $req = $this->db->prepare("
            SELECT 
                o.id, o.amount, o.status, o.created_at, o.expires_at,
                u.first_name, u.last_name, u.email as sender_email,
                v.marque, v.modele, v.prix as prix_vehicule
            FROM offers o
            LEFT JOIN users u ON o.sender_id = u.id
            LEFT JOIN conversations c ON o.conversation_id = c.id
            LEFT JOIN vehicles v ON c.vehicle_id = v.id
            ORDER BY o.created_at DESC
            LIMIT :limite
        ");
        $req->bindValue(':limite', $limite, PDO::PARAM_INT);
        $req->execute();
        
        return $req->fetchAll();
    }
    
    // ========================================================================
    // STATISTIQUES FAVORIS
    // ========================================================================
    
    /**
     * Obtient les statistiques des favoris
     * 
     * @return array Statistiques favoris
     */
    public function obtenirStatistiquesFavoris() {
        // Total favoris
        $reqTotal = $this->db->query("SELECT COUNT(*) as total FROM favorites");
        $total = $reqTotal->fetch()['total'];
        
        // Véhicules les plus favorisés
        $reqTopVehicules = $this->db->query("
            SELECT 
                v.id, v.marque, v.modele, v.prix, COUNT(f.user_id) as nb_favoris
            FROM favorites f
            JOIN vehicles v ON f.vehicle_id = v.id
            GROUP BY v.id
            ORDER BY nb_favoris DESC
            LIMIT 10
        ");
        $topVehicules = $reqTopVehicules->fetchAll();
        
        // Favoris des dernières 24h
        $reqFavoris24h = $this->db->query("
            SELECT COUNT(*) as total FROM favorites 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $favoris24h = $reqFavoris24h->fetch()['total'];
        
        return [
            'total' => (int)$total,
            'top_vehicules' => $topVehicules,
            'favoris_24h' => (int)$favoris24h
        ];
    }
    
    // ========================================================================
    // ACTIVITÉ RÉCENTE (TIMELINE)
    // ========================================================================
    
    /**
     * Obtient un flux d'activité récent combiné
     * 
     * @param int $limite Nombre d'éléments
     * @return array Activités récentes
     */
    public function obtenirActiviteRecente($limite = 30) {
        // On combine plusieurs sources d'activité
        $activites = [];
        
        // Nouveaux utilisateurs (5 derniers)
        $reqUsers = $this->db->query("
            SELECT 'nouveau_utilisateur' as type, 
                   id, first_name, last_name, email, created_at
            FROM users 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        while ($row = $reqUsers->fetch()) {
            $activites[] = [
                'type' => 'nouveau_utilisateur',
                'data' => $row,
                'timestamp' => $row['created_at']
            ];
        }
        
        // Nouveaux véhicules (5 derniers)
        $reqVehicles = $this->db->query("
            SELECT 'nouveau_vehicule' as type,
                   v.id, v.marque, v.modele, v.prix, v.created_at,
                   u.first_name, u.last_name
            FROM vehicles v
            LEFT JOIN users u ON v.user_id = u.id
            ORDER BY v.created_at DESC 
            LIMIT 5
        ");
        while ($row = $reqVehicles->fetch()) {
            $activites[] = [
                'type' => 'nouveau_vehicule',
                'data' => $row,
                'timestamp' => $row['created_at']
            ];
        }
        
        // Nouvelles offres (5 dernières)
        $reqOffres = $this->db->query("
            SELECT 'nouvelle_offre' as type,
                   o.id, o.amount, o.status, o.created_at,
                   u.first_name, u.last_name,
                   v.marque, v.modele
            FROM offers o
            LEFT JOIN users u ON o.sender_id = u.id
            LEFT JOIN conversations c ON o.conversation_id = c.id
            LEFT JOIN vehicles v ON c.vehicle_id = v.id
            ORDER BY o.created_at DESC 
            LIMIT 5
        ");
        while ($row = $reqOffres->fetch()) {
            $activites[] = [
                'type' => 'nouvelle_offre',
                'data' => $row,
                'timestamp' => $row['created_at']
            ];
        }
        
        // Trier par date décroissante
        usort($activites, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($activites, 0, $limite);
    }
    
    // ========================================================================
    // DONNÉES GRAPHIQUES (pour charts)
    // ========================================================================
    
    /**
     * Obtient les inscriptions par jour sur les 30 derniers jours
     * 
     * @return array Données pour graphique
     */
    public function obtenirInscriptionsParJour() {
        $req = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM users
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        
        return $req->fetchAll();
    }
    
    /**
     * Obtient les annonces par jour sur les 30 derniers jours
     * 
     * @return array Données pour graphique
     */
    public function obtenirAnnoncesParJour() {
        $req = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM vehicles
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        
        return $req->fetchAll();
    }
    
    /**
     * Obtient les messages par jour sur les 30 derniers jours
     * 
     * @return array Données pour graphique
     */
    public function obtenirMessagesParJour() {
        $req = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM messages
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        
        return $req->fetchAll();
    }
    
    // ========================================================================
    // RÉSUMÉ GLOBAL (Dashboard principal)
    // ========================================================================
    
    /**
     * Obtient un résumé global de toutes les statistiques
     * 
     * @return array Résumé complet
     */
    public function obtenirResume() {
        return [
            'utilisateurs' => $this->obtenirStatistiquesUtilisateurs(),
            'vehicules' => $this->obtenirStatistiquesVehicules(),
            'messagerie' => $this->obtenirStatistiquesMessagerie(),
            'offres' => $this->obtenirStatistiquesOffres(),
            'favoris' => $this->obtenirStatistiquesFavoris(),
            'activite_recente' => $this->obtenirActiviteRecente(15),
            'derniers_vehicules' => $this->obtenirDerniersVehicules(5),
            'marques_populaires' => $this->obtenirMarquesPopulaires(5)
        ];
    }
}
