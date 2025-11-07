<?php
/**
 * Wrapper public pour l'API véhicules
 * 
 * Ce fichier sert de point d'entrée public pour l'API des véhicules.
 * Il redirige simplement vers le fichier API principal situé hors du DocumentRoot
 * pour des raisons de sécurité (le dossier /api/ n'est pas accessible directement).
 * 
 * URL d'accès : /public/api/api.php
 * Fichier réel : /api/api.php
 */
require __DIR__ . '/../../api/api.php';
