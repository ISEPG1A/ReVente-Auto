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
            Utils::envoyerJSON(['erreur' => 'Authentification requise'], 401);
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
                } elseif (isset($_GET['id_conversation'])) {
                    $this->obtenirMessages((int)$_GET['id_conversation']);
                } else {
                    $this->obtenirConversations();
                }
            } elseif ($methode === 'POST') {
                $donnees = Utils::lireCorpsJSON();
                if (isset($donnees['action']) && $donnees['action'] === 'creer_conv') {
                    $this->creerConversation($donnees);
                } else {
                    $this->envoyerMessage($donnees);
                }
            } else {
                Utils::envoyerJSON(['erreur' => 'Méthode non autorisée'], 405);
            }
        } catch (Exception $e) {
            Utils::envoyerJSON(['erreur' => $e->getMessage()], 500);
        }
    }

    private function obtenirInfosUtilisateur() {
        $utilisateur = $this->modele->obtenirInfosUtilisateur($this->idUtilisateur);
        if (!$utilisateur) Utils::envoyerJSON(['erreur' => 'Utilisateur introuvable'], 404);
        Utils::envoyerJSON(['utilisateur' => $utilisateur]);
    }

    private function compterNonLus() {
        $compte = $this->modele->compterMessagesNonLus($this->idUtilisateur);
        Utils::envoyerJSON(['compte' => $compte]);
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
        
        Utils::envoyerJSON($conversations);
    }

    private function obtenirMessages($idConv) {
        $conv = $this->modele->verifierAppartenanceConversation($idConv, $this->idUtilisateur);
        
        if (!$conv) Utils::envoyerJSON(['erreur' => 'Conversation introuvable ou accès refusé'], 403);

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
                    $msg['contenu_clair'] = CryptoService::dechiffrerMessage(
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

        Utils::envoyerJSON(['conversation' => $conv, 'messages' => $messages]);
    }

    private function creerConversation($donnees) {
        $idVehicule = (int)$donnees['id_vehicule'];
        $idVendeur = (int)$donnees['id_vendeur'];
        
        if ($idVendeur === $this->idUtilisateur) Utils::envoyerJSON(['erreur' => 'Vous ne pouvez pas vous contacter vous-même'], 400);

        $existant = $this->modele->trouverConversation($idVehicule, $this->idUtilisateur, $idVendeur);
        
        if ($existant) {
            Utils::envoyerJSON(['id' => $existant['id']]);
        } else {
            $nouvelId = $this->modele->creerConversation($idVehicule, $this->idUtilisateur, $idVendeur);
            Utils::envoyerJSON(['id' => $nouvelId], 201);
        }
    }

    private function envoyerMessage($donnees) {
        $idConv = (int)($donnees['id_conversation'] ?? 0);
        $contenu = trim($donnees['contenu'] ?? '');
        
        if (empty($contenu)) Utils::envoyerJSON(['erreur' => 'Message vide'], 400);

        $conv = $this->modele->obtenirParticipantsConversation($idConv);
        
        if (!$conv) Utils::envoyerJSON(['erreur' => 'Conversation introuvable'], 404);
        
        $idDestinataire = ($conv['buyer_id'] == $this->idUtilisateur) ? $conv['seller_id'] : $conv['buyer_id'];
        
        $cles = $this->modele->obtenirClesPubliques([$idDestinataire, $this->idUtilisateur]);
        
        $clePubDest = $cles[$idDestinataire] ?? null;
        $clePubExp = $cles[$this->idUtilisateur] ?? null;
        
        if (!$clePubDest) Utils::envoyerJSON(['erreur' => 'Le destinataire n\'a pas de clé de chiffrement'], 500);
        if (!$clePubExp) Utils::envoyerJSON(['erreur' => 'Vous n\'avez pas de clé de chiffrement'], 500);

        $donneesChiffrees = CryptoService::chiffrerMessagePourDeux($contenu, $clePubDest, $clePubExp);
        
        $this->modele->enregistrerMessage($idConv, $this->idUtilisateur, $donneesChiffrees);
        
        Utils::envoyerJSON(['ok' => true]);
    }
}

$controleur = new ControleurMessagerie();
$controleur->traiterRequete();
