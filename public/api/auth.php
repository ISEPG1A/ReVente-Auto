<?php
/**
 * Wrapper public pour l'API d'authentification
 * 
 * Ce fichier sert de point d'entrée public pour l'API d'authentification.
 * Il redirige simplement vers le fichier API principal situé hors du DocumentRoot
 * pour des raisons de sécurité (le dossier /api/ n'est pas accessible directement).
 * 
 * URL d'accès : /public/api/auth.php
 * Fichier réel : /api/auth.php
 */
require __DIR__ . '/../../api/auth.php';
