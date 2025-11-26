<?php

if (session_status() === PHP_SESSION_NONE) session_start();

class ControleurDetails {
    private $modele;

    public function __construct() {
        $this->modele = new ModeleVehicule();
    }

    public function traiterRequete() {
        $methode = $_SERVER['REQUEST_METHOD'];

        switch ($methode) {
            case 'GET':
                $this->gererGet();
                break;
            case 'DELETE':
                $this->gererDelete();
                break;
            default:
                Utils::envoyerJSON(['error' => 'Méthode non autorisée'], 405);
        }
    }

    private function gererGet() {
        if (isset($_GET['id'])) {
            $vehicule = $this->modele->obtenirParId((int)$_GET['id']);
            if ($vehicule) {
                Utils::envoyerJSON($vehicule);
            } else {
                Utils::envoyerJSON(['error' => 'Véhicule introuvable'], 404);
            }
        } else {
            Utils::envoyerJSON(['error' => 'ID manquant'], 400);
        }
    }

    private function gererDelete() {
        if (empty($_SESSION['user'])) {
            Utils::envoyerJSON(['error' => 'Authentification requise'], 401);
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            Utils::envoyerJSON(['error' => 'ID invalide'], 422);
        }

        $userId = (int)$_SESSION['user']['id'];
        $isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

        if ($this->modele->supprimer($id, $userId, $isAdmin)) {
            Utils::envoyerJSON(['ok' => true]);
        } else {
            Utils::envoyerJSON(['error' => 'Véhicule introuvable ou suppression impossible'], 404);
        }
    }
}

$controleur = new ControleurDetails();
$controleur->traiterRequete();
