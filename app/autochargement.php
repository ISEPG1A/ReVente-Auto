<?php

/**
 * Autoloader simple pour l'architecture MVC sans namespaces.
 * Parcourt récursivement le dossier 'app' pour trouver la classe demandée.
 */
spl_autoload_register(function ($classe) {
    // Le dossier racine où chercher les classes (le dossier 'app')
    $repertoireBase = __DIR__;

    // On utilise un itérateur récursif pour parcourir tous les sous-dossiers
    $iterateur = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($repertoireBase, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterateur as $fichier) {
        // On cherche un fichier .php qui porte le même nom que la classe
        if ($fichier->isFile() && $fichier->getExtension() === 'php') {
            if ($fichier->getBasename('.php') === $classe) {
                require_once $fichier->getPathname();
                return; // On a trouvé et chargé la classe, on s'arrête
            }
        }
    }
});
