<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurMessagerie {
    private $modele;
    private $idUtilisateur;

    public function __construct() {
        $this->modele = new ModeleMessagerie();
    }

    private function verifierAuthentification() {
        if (empty($_SESSION['user'])) {
            Utilitaires::envoyerJSON(['erreur' => 'Authentification requise'], 401);
        }
        $this->idUtilisateur = (int)$_SESSION['user']['id'];
    }

    public function traiterRequete() {
        $this->verifierAuthentification();
        $methode = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        try {
            if ($methode === 'GET') {
                if ($action === 'infos_utilisateur') {
                    $this->obtenirInfosUtilisateur();
                } elseif ($action === 'compter_non_lus') {
                    $this->compterNonLus();
                } elseif ($action === 'proposition') {
                    $this->obtenirProposition();
                } elseif ($action === 'propositions') {
                    $this->obtenirPropositions();
                } elseif (isset($_GET['id_conversation'])) {
                    $this->obtenirMessages((int)$_GET['id_conversation']);
                } else {
                    $this->obtenirConversations();
                }
            } elseif ($methode === 'POST') {
                $donnees = Utilitaires::lireCorpsJSON();
                $actionPost = $donnees['action'] ?? '';
                
                if ($actionPost === 'creer_conv') {
                    $this->creerConversation($donnees);
                } elseif ($actionPost === 'creer_proposition') {
                    $this->creerProposition($donnees);
                } elseif ($actionPost === 'accepter_proposition') {
                    $this->accepterProposition($donnees);
                } elseif ($actionPost === 'refuser_proposition') {
                    $this->refuserProposition($donnees);
                } elseif ($actionPost === 'annuler_proposition') {
                    $this->annulerProposition($donnees);
                } elseif ($actionPost === 'payer_proposition') {
                    $this->payerProposition($donnees);
                } else {
                    $this->envoyerMessage($donnees);
                }
            } else {
                Utilitaires::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
            }
        } catch (Exception $e) {
            Utilitaires::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }

    private function obtenirInfosUtilisateur() {
        $utilisateur = $this->modele->obtenirInfosUtilisateur($this->idUtilisateur);
        if (!$utilisateur) Utilitaires::envoyerJSON(['erreur' => 'Utilisateur introuvable'], 404);
        Utilitaires::envoyerJSON(['utilisateur' => $utilisateur]);
    }

    private function compterNonLus() {
        $compte = $this->modele->compterMessagesNonLus($this->idUtilisateur);
        Utilitaires::envoyerJSON(['compte' => $compte]);
    }

    private function obtenirConversations() {
        $conversations = $this->modele->obtenirConversations($this->idUtilisateur);
        
        foreach ($conversations as &$c) {
            $estAcheteur = ($c['buyer_id'] == $this->idUtilisateur);
            $c['nom_autre_utilisateur'] = $estAcheteur ? ($c['seller_name'] . ' ' . $c['seller_lastname']) : ($c['buyer_name'] . ' ' . $c['buyer_lastname']);
            $c['id_autre_utilisateur'] = $estAcheteur ? $c['seller_id'] : $c['buyer_id'];
            $c['avatar_autre_utilisateur'] = $estAcheteur ? ($c['seller_avatar'] ?? null) : ($c['buyer_avatar'] ?? null);
            $c['non_lu'] = (int)($c['messages_non_lus'] ?? 0) > 0;
            $c['nb_non_lus'] = (int)($c['messages_non_lus'] ?? 0);
        }
        
        Utilitaires::envoyerJSON($conversations);
    }

    private function obtenirMessages($idConv) {
        $conv = $this->modele->verifierAppartenanceConversation($idConv, $this->idUtilisateur);
        
        if (!$conv) Utilitaires::envoyerJSON(['erreur' => 'Conversation introuvable ou accès refusé'], 403);

        $this->modele->marquerMessagesCommeLus($idConv, $this->idUtilisateur);

        $messages = $this->modele->obtenirMessages($idConv);
        $maClePrivee = $this->modele->obtenirClePrivee($this->idUtilisateur);

        foreach ($messages as &$msg) {
            $estExpediteur = ($msg['sender_id'] == $this->idUtilisateur);
            $cleAUtiliser = $estExpediteur ? ($msg['encrypted_key_sender'] ?? null) : $msg['encrypted_key'];

            if (!$cleAUtiliser) {
                $msg['contenu_clair'] = "[Message ancien chiffré uniquement pour le destinataire]";
            } else {
                try {
                    $msg['contenu_clair'] = ServiceChiffrement::dechiffrerMessage(
                        $msg['content'], 
                        $msg['iv'], 
                        $cleAUtiliser, 
                        $maClePrivee
                    );
                } catch (Exception $e) {
                    $msg['contenu_clair'] = "[Erreur de déchiffrement]";
                }
            }
            unset($msg['content'], $msg['iv'], $msg['encrypted_key'], $msg['encrypted_key_sender']);
        }

        Utilitaires::envoyerJSON(['conversation' => $conv, 'messages' => $messages]);
    }

    private function creerConversation($donnees) {
        $idVehicule = (int)$donnees['id_vehicule'];
        $idVendeur = (int)$donnees['id_vendeur'];
        
        if ($idVendeur === $this->idUtilisateur) Utilitaires::envoyerJSON(['erreur' => 'Vous ne pouvez pas vous contacter vous-même'], 400);

        $existant = $this->modele->trouverConversation($idVehicule, $this->idUtilisateur, $idVendeur);
        
        if ($existant) {
            Utilitaires::envoyerJSON(['id' => $existant['id']]);
        } else {
            $nouvelId = $this->modele->creerConversation($idVehicule, $this->idUtilisateur, $idVendeur);
            
            // Incrémenter le compteur de contacts du véhicule
            $modeleVehicule = new ModeleVehicule();
            $modeleVehicule->incrementerContacts($idVehicule);
            
            Utilitaires::envoyerJSON(['id' => $nouvelId], 201);
        }
    }

    private function envoyerMessage($donnees) {
        $idConv = (int)($donnees['id_conversation'] ?? 0);
        $contenu = trim($donnees['contenu'] ?? '');
        
        // SÉCURITÉ : Validation du contenu du message
        if (empty($contenu)) {
            Utilitaires::envoyerJSON(['erreur' => 'Message vide'], 400);
        }
        
        // Limiter la longueur du message (protection contre abus)
        if (strlen($contenu) > 5000) {
            Utilitaires::envoyerJSON(['erreur' => 'Message trop long (maximum 5000 caractères)'], 400);
        }
        
        // Vérifier que l'ID conversation est valide
        if ($idConv <= 0) {
            Utilitaires::envoyerJSON(['erreur' => 'ID conversation invalide'], 400);
        }

        $conv = $this->modele->obtenirParticipantsConversation($idConv);
        
        if (!$conv) Utilitaires::envoyerJSON(['erreur' => 'Conversation introuvable'], 404);
        
        $idDestinataire = ($conv['buyer_id'] == $this->idUtilisateur) ? $conv['seller_id'] : $conv['buyer_id'];
        
        $cles = $this->modele->obtenirClesPubliques([$idDestinataire, $this->idUtilisateur]);
        
        $clePubDest = $cles[$idDestinataire] ?? null;
        $clePubExp = $cles[$this->idUtilisateur] ?? null;
        
        if (!$clePubDest) Utilitaires::envoyerJSON(['erreur' => 'Le destinataire n\'a pas de clé de chiffrement'], 500);
        if (!$clePubExp) Utilitaires::envoyerJSON(['erreur' => 'Vous n\'avez pas de clé de chiffrement'], 500);

        $donneesChiffrees = ServiceChiffrement::chiffrerMessagePourDeux($contenu, $clePubDest, $clePubExp);
        
        $this->modele->enregistrerMessage($idConv, $this->idUtilisateur, $donneesChiffrees);
        
        Utilitaires::envoyerJSON(['ok' => true]);
    }

    // =============================================
    // GESTION DES PROPOSITIONS DE PRIX
    // =============================================

    private function obtenirProposition() {
        $idConv = (int)($_GET['id_conversation'] ?? 0);
        if (!$idConv) {
            Utilitaires::envoyerJSON(['erreur' => 'ID conversation requis'], 400);
        }

        $conv = $this->modele->verifierAppartenanceConversation($idConv, $this->idUtilisateur);
        if (!$conv) {
            Utilitaires::envoyerJSON(['erreur' => 'Accès refusé'], 403);
        }

        $proposition = $this->modele->obtenirPropositionActive($idConv);
        Utilitaires::envoyerJSON(['proposition' => $proposition]);
    }

    private function obtenirPropositions() {
        $idConv = (int)($_GET['id_conversation'] ?? 0);
        if (!$idConv) {
            Utilitaires::envoyerJSON(['erreur' => 'ID conversation requis'], 400);
        }

        $conv = $this->modele->verifierAppartenanceConversation($idConv, $this->idUtilisateur);
        if (!$conv) {
            Utilitaires::envoyerJSON(['erreur' => 'Accès refusé'], 403);
        }

        $propositions = $this->modele->obtenirPropositions($idConv);
        Utilitaires::envoyerJSON(['propositions' => $propositions]);
    }

    private function creerProposition($donnees) {
        $idConv = (int)($donnees['id_conversation'] ?? 0);
        $montant = (float)($donnees['montant'] ?? 0);

        // SÉCURITÉ : Validation stricte du montant
        if (!$idConv || $montant <= 0) {
            Utilitaires::envoyerJSON(['erreur' => 'Données invalides'], 400);
        }
        
        // Montant maximum raisonnable (999 millions)
        if ($montant > 999999999) {
            Utilitaires::envoyerJSON(['erreur' => 'Montant trop élevé'], 400);
        }
        
        // Vérifier que le montant a maximum 2 décimales
        if (round($montant, 2) != $montant) {
            Utilitaires::envoyerJSON(['erreur' => 'Le montant ne peut avoir que 2 décimales maximum'], 400);
        }

        $conv = $this->modele->verifierAppartenanceConversation($idConv, $this->idUtilisateur);
        if (!$conv) {
            Utilitaires::envoyerJSON(['erreur' => 'Accès refusé'], 403);
        }

        $idOffre = $this->modele->creerProposition($idConv, $this->idUtilisateur, $montant);
        $proposition = $this->modele->obtenirPropositionActive($idConv);
        
        Utilitaires::envoyerJSON(['ok' => true, 'proposition' => $proposition], 201);
    }

    private function accepterProposition($donnees) {
        $idOffre = (int)($donnees['id_offre'] ?? 0);
        if (!$idOffre) {
            Utilitaires::envoyerJSON(['erreur' => 'ID offre requis'], 400);
        }

        $this->modele->accepterProposition($idOffre, $this->idUtilisateur);
        Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Proposition acceptée']);
    }

    private function refuserProposition($donnees) {
        $idOffre = (int)($donnees['id_offre'] ?? 0);
        if (!$idOffre) {
            Utilitaires::envoyerJSON(['erreur' => 'ID offre requis'], 400);
        }

        $this->modele->refuserProposition($idOffre, $this->idUtilisateur);
        Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Proposition refusée']);
    }

    private function annulerProposition($donnees) {
        $idOffre = (int)($donnees['id_offre'] ?? 0);
        if (!$idOffre) {
            Utilitaires::envoyerJSON(['erreur' => 'ID offre requis'], 400);
        }

        $this->modele->annulerProposition($idOffre, $this->idUtilisateur);
        Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Proposition annulée']);
    }

    private function payerProposition($donnees) {
        $idOffre = (int)($donnees['id_offre'] ?? 0);
        if (!$idOffre) {
            Utilitaires::envoyerJSON(['erreur' => 'ID offre requis'], 400);
        }

        $this->modele->marquerCommePaye($idOffre, $this->idUtilisateur);
        Utilitaires::envoyerJSON(['ok' => true, 'message' => 'Paiement effectué']);
    }
}

$controleur = new ControleurMessagerie();
$controleur->traiterRequete();
