<?php

/**
 * Classe gérant la sécurité de l'application
 * Fournit des méthodes pour sécuriser les en-têtes HTTP et les données
 */
class Securite {

    /**
     * Ajoute les en-têtes de sécurité HTTP pour renforcer la protection du site
     * Contre XSS, Clickjacking, Sniffing, etc.
     */
    public static function ajouterEnTetes(): void {
        // 1. HSTS (HTTP Strict Transport Security)
        // Force le navigateur à utiliser HTTPS pendant 1 an, incluant les sous-domaines
        // Note : À activer uniquement si le site est servi en HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        }

        // 2. Protection contre le XSS (Cross-Site Scripting)
        // Active le filtre XSS du navigateur (pour les anciens navigateurs)
        header("X-XSS-Protection: 1; mode=block");

        // 3. Protection contre le Clickjacking (X-Frame-Options)
        // Empêche le site d'être affiché dans une iframe (sauf même origine)
        header("X-Frame-Options: SAMEORIGIN");

        // 4. Protection contre le reniflage de type MIME (MIME Sniffing)
        // Force le navigateur à respecter le Content-Type déclaré
        header("X-Content-Type-Options: nosniff");

        // 5. Politique de référent (Referrer Policy)
        // Contrôle les informations envoyées dans l'en-tête Referer
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // 6. Politique de sécurité du contenu (Content Security Policy - CSP)
        // Définit les sources autorisées pour les contenus (scripts, styles, images, etc.)
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://unpkg.com", // 'unsafe-inline' nécessaire pour les scripts modules dans les vues
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://unpkg.com", // data: pour les images base64, blob: pour les prévisualisations
            "connect-src 'self' https://nominatim.openstreetmap.org https://geo.api.gouv.fr", // Autorise les requêtes AJAX/Fetch vers le même domaine et APIs externes
            "frame-ancestors 'self'", // Équivalent moderne de X-Frame-Options
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        header("Content-Security-Policy: " . implode('; ', $csp));

        // 7. Permissions Policy (anciennement Feature Policy)
        // Autorise la géolocalisation pour la recherche par proximité
        header("Permissions-Policy: geolocation=(self), microphone=(), camera=(), payment=()");
    }

    /**
     * Nettoie une chaîne de caractères pour prévenir les failles XSS
     * À utiliser lors de l'affichage de données utilisateur
     * 
     * @param string $donnee La donnée à nettoyer
     * @return string La donnée sécurisée
     */
    public static function echapper(string $donnee): string {
        return htmlspecialchars($donnee, ENT_QUOTES, 'UTF-8');
    }
}
