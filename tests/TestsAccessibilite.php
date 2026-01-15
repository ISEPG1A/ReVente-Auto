<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TESTS D'ACCESSIBILITÉ WEB - ReVente-Auto
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Tests d'accessibilité WCAG 2.1 :
 * - Structure HTML sémantique
 * - Attributs ARIA
 * - Contraste des couleurs
 * - Navigation au clavier
 * - Images et alternatives textuelles
 * - Formulaires accessibles
 * - Liens et boutons
 * 
 * @author  Équipe ReVente-Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/TestsBase.php';

class TestsAccessibilite extends TestsBase {
    
    protected string $nomTest = "TESTS D'ACCESSIBILITÉ WEB - ReVente-Auto";
    
    /**
     * Exécute tous les tests d'accessibilité
     */
    public function executer(): array {
        $this->afficherEntete();
        
        $this->testerStructureHTML();
        $this->testerAttributsARIA();
        $this->testerImagesAlternatives();
        $this->testerFormulaires();
        $this->testerLiensEtBoutons();
        $this->testerContraste();
        $this->testerNavigation();
        $this->testerResponsive();
        
        $this->afficherResume();
        
        return $this->getStatistiques();
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 1. STRUCTURE HTML SÉMANTIQUE
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerStructureHTML(): void {
        $this->afficherSection("1. STRUCTURE HTML SÉMANTIQUE");
        
        $pages = ['', 'galerie', 'faq', 'contact', 'connexion'];
        
        foreach ($pages as $page) {
            $response = $this->httpGet($this->baseUrl . $page);
            $html = $response['body'];
            $nomPage = $page ?: 'accueil';
            
            // DOCTYPE HTML5
            $doctype = preg_match('/<!DOCTYPE html>/i', $html);
            $this->assertTrue($doctype, "Page $nomPage: DOCTYPE HTML5");
            
            // Attribut lang
            $lang = preg_match('/<html[^>]*lang=["\']fr["\'][^>]*>/i', $html);
            $this->assertTrue($lang, "Page $nomPage: attribut lang='fr'", "", Criticite::IMPORTANT);
            
            // Balise title
            $title = preg_match('/<title>[^<]+<\/title>/i', $html);
            $this->assertTrue($title, "Page $nomPage: balise <title>", "", Criticite::IMPORTANT);
            
            // Meta description
            $metaDesc = preg_match('/<meta[^>]*name=["\']description["\'][^>]*>/i', $html);
            $this->assertTrue($metaDesc, "Page $nomPage: meta description");
            
            // Meta viewport
            $viewport = preg_match('/<meta[^>]*name=["\']viewport["\'][^>]*>/i', $html);
            $this->assertTrue($viewport, "Page $nomPage: meta viewport", "", Criticite::IMPORTANT);
            
            // Structure sémantique
            $header = preg_match('/<header[^>]*>/i', $html);
            $main = preg_match('/<main[^>]*>/i', $html);
            $footer = preg_match('/<footer[^>]*>/i', $html);
            $nav = preg_match('/<nav[^>]*>/i', $html);
            
            $this->assertTrue($header, "Page $nomPage: balise <header>");
            $this->assertTrue($main, "Page $nomPage: balise <main>", "", Criticite::IMPORTANT);
            $this->assertTrue($footer, "Page $nomPage: balise <footer>");
            $this->assertTrue($nav, "Page $nomPage: balise <nav>");
            
            // Hiérarchie des titres (h1 unique)
            preg_match_all('/<h1[^>]*>/i', $html, $h1s);
            $h1Unique = count($h1s[0]) === 1;
            $this->assertTrue($h1Unique, "Page $nomPage: un seul <h1>", count($h1s[0]) . " trouvés", Criticite::IMPORTANT);
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 2. ATTRIBUTS ARIA
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerAttributsARIA(): void {
        $this->afficherSection("2. ATTRIBUTS ARIA");
        
        $response = $this->httpGet($this->baseUrl);
        $html = $response['body'];
        
        // Landmarks ARIA
        $this->afficherSousSection("Landmarks ARIA");
        
        $landmarks = [
            'role="banner"' => 'Banner (header)',
            'role="navigation"' => 'Navigation',
            'role="main"' => 'Main content',
            'role="contentinfo"' => 'Content info (footer)',
            'role="search"' => 'Recherche'
        ];
        
        foreach ($landmarks as $role => $description) {
            $present = stripos($html, $role) !== false;
            // Note: les éléments sémantiques HTML5 ont des rôles implicites
            $this->afficherInfo("$description: " . ($present ? "explicite" : "implicite ou absent"));
        }
        
        // aria-label sur éléments interactifs
        $this->afficherSousSection("Labels ARIA");
        
        // Boutons avec icônes
        preg_match_all('/<button[^>]*>/i', $html, $boutons);
        $boutonsAvecLabel = 0;
        foreach ($boutons[0] as $bouton) {
            if (preg_match('/aria-label|title|>.*[a-zA-Z]/i', $bouton)) {
                $boutonsAvecLabel++;
            }
        }
        
        $totalBoutons = count($boutons[0]);
        $this->assertTrue($boutonsAvecLabel >= $totalBoutons * 0.8, 
            "Boutons avec label accessible ($boutonsAvecLabel/$totalBoutons)");
        
        // aria-expanded pour menus
        $ariaExpanded = stripos($html, 'aria-expanded') !== false;
        $this->assertTrue($ariaExpanded, "aria-expanded utilisé pour menus déroulants");
        
        // aria-hidden sur icônes décoratives
        $ariaHidden = stripos($html, 'aria-hidden="true"') !== false;
        $this->assertTrue($ariaHidden, "aria-hidden sur éléments décoratifs");
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 3. IMAGES ET ALTERNATIVES TEXTUELLES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerImagesAlternatives(): void {
        $this->afficherSection("3. IMAGES ET ALTERNATIVES TEXTUELLES");
        
        $pages = ['', 'galerie', 'equipe'];
        
        foreach ($pages as $page) {
            $response = $this->httpGet($this->baseUrl . $page);
            $html = $response['body'];
            $nomPage = $page ?: 'accueil';
            
            // Trouver toutes les images
            preg_match_all('/<img[^>]*>/i', $html, $images);
            
            $imagesAvecAlt = 0;
            $imagesDecoratives = 0;
            $imagesSansAlt = [];
            
            foreach ($images[0] as $img) {
                if (preg_match('/alt=["\'][^"\']*["\']/', $img)) {
                    $imagesAvecAlt++;
                    
                    // Alt vide = décorative (ok)
                    if (preg_match('/alt=["\']["\']/', $img)) {
                        $imagesDecoratives++;
                    }
                } elseif (preg_match('/role=["\']presentation["\']|aria-hidden=["\']true["\']/', $img)) {
                    $imagesDecoratives++;
                } else {
                    // Extraire src pour identifier l'image
                    preg_match('/src=["\']([^"\']+)["\']/', $img, $src);
                    $imagesSansAlt[] = $src[1] ?? 'inconnu';
                }
            }
            
            $totalImages = count($images[0]);
            if ($totalImages > 0) {
                $pourcentageAlt = round(($imagesAvecAlt / $totalImages) * 100, 1);
                $this->assertTrue($pourcentageAlt >= 90, 
                    "Page $nomPage: images avec alt ($imagesAvecAlt/$totalImages = $pourcentageAlt%)",
                    "",
                    Criticite::IMPORTANT);
                
                if (!empty($imagesSansAlt)) {
                    $this->afficherAvertissement("Images sans alt: " . implode(', ', array_slice($imagesSansAlt, 0, 3)));
                }
            }
        }
        
        // SVG avec rôle et titre
        $this->afficherSousSection("SVG accessibles");
        $response = $this->httpGet($this->baseUrl);
        $html = $response['body'];
        
        preg_match_all('/<svg[^>]*>.*?<\/svg>/is', $html, $svgs);
        $svgAccessibles = 0;
        
        foreach ($svgs[0] as $svg) {
            if (preg_match('/role=["\']img["\']|aria-label|<title>/i', $svg) 
                || preg_match('/aria-hidden=["\']true["\']/i', $svg)) {
                $svgAccessibles++;
            }
        }
        
        $totalSvg = count($svgs[0]);
        if ($totalSvg > 0) {
            $this->assertTrue($svgAccessibles >= $totalSvg * 0.8, 
                "SVG accessibles ($svgAccessibles/$totalSvg)");
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 4. FORMULAIRES ACCESSIBLES
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerFormulaires(): void {
        $this->afficherSection("4. FORMULAIRES ACCESSIBLES");
        
        // Note: '/inscription' n'existe pas, le formulaire est intégré dans '/connexion'
        $pages = ['connexion', 'contact'];
        
        foreach ($pages as $page) {
            $response = $this->httpGet($this->baseUrl . $page);
            $html = $response['body'];
            
            // Trouver les inputs
            preg_match_all('/<input[^>]*>/i', $html, $inputs);
            
            $inputsAvecLabel = 0;
            $inputsSansLabel = [];
            
            foreach ($inputs[0] as $input) {
                // Ignorer les inputs hidden, submit et ceux avec attribut hidden (honeypots)
                if (preg_match('/type=["\']hidden["\']|type=["\']submit["\']|\shidden[\s>]/i', $input)) {
                    continue;
                }
                
                // Vérifier label associé
                $hasId = preg_match('/id=["\']([^"\']+)["\']/', $input, $idMatch);
                $hasAriaLabel = preg_match('/aria-label=["\'][^"\']+["\']/', $input);
                $hasPlaceholder = preg_match('/placeholder=["\'][^"\']+["\']/', $input);
                
                if ($hasId && $idMatch[1]) {
                    $labelFor = preg_match('/for=["\']' . preg_quote($idMatch[1], '/') . '["\']/', $html);
                    if ($labelFor || $hasAriaLabel) {
                        $inputsAvecLabel++;
                    } else {
                        $inputsSansLabel[] = $idMatch[1];
                    }
                } elseif ($hasAriaLabel) {
                    $inputsAvecLabel++;
                } else {
                    preg_match('/name=["\']([^"\']+)["\']/', $input, $name);
                    $inputsSansLabel[] = $name[1] ?? 'inconnu';
                }
            }
            
            // Compter inputs valides (non hidden/submit)
            $inputsValides = 0;
            foreach ($inputs[0] as $input) {
                if (!preg_match('/type=["\']hidden["\']|type=["\']submit["\']/i', $input)) {
                    $inputsValides++;
                }
            }
            
            if ($inputsValides > 0) {
                $pourcentage = round(($inputsAvecLabel / $inputsValides) * 100, 1);
                $this->assertTrue($pourcentage >= 80, 
                    "Page $page: inputs avec label ($inputsAvecLabel/$inputsValides = $pourcentage%)",
                    "",
                    Criticite::IMPORTANT);
                
                if (!empty($inputsSansLabel)) {
                    $this->afficherAvertissement("Inputs sans label: " . implode(', ', array_slice($inputsSansLabel, 0, 3)));
                }
            }
            
            // Attributs autocomplete
            $autocomplete = preg_match('/autocomplete=["\'][^"\']+["\']/i', $html);
            $this->assertTrue($autocomplete, "Page $page: attributs autocomplete");
            
            // Messages d'erreur avec aria-describedby
            $ariaDescribedby = preg_match('/aria-describedby/i', $html);
            $this->afficherInfo("Page $page: aria-describedby " . ($ariaDescribedby ? "présent" : "absent"));
        }
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 5. LIENS ET BOUTONS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerLiensEtBoutons(): void {
        $this->afficherSection("5. LIENS ET BOUTONS");
        
        $response = $this->httpGet($this->baseUrl);
        $html = $response['body'];
        
        // Liens avec texte descriptif
        $this->afficherSousSection("Qualité des liens");
        
        preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $html, $liens);
        
        $liensVagues = [];
        $textesVagues = ['cliquez ici', 'ici', 'lire plus', 'voir plus', 'en savoir plus'];
        
        foreach ($liens[1] as $i => $texte) {
            $texteNettoye = strtolower(trim(strip_tags($texte)));
            if (in_array($texteNettoye, $textesVagues) || strlen($texteNettoye) < 2) {
                // Vérifier si le lien a un aria-label
                if (!preg_match('/aria-label/i', $liens[0][$i])) {
                    $liensVagues[] = $texteNettoye;
                }
            }
        }
        
        $this->assertTrue(count($liensVagues) < 5, 
            "Liens avec texte descriptif (" . count($liensVagues) . " vagues)",
            implode(', ', array_slice($liensVagues, 0, 3)));
        
        // Liens externes avec indication
        $this->afficherSousSection("Liens externes");
        
        preg_match_all('/<a[^>]*href=["\']https?:\/\/(?!localhost)[^"\']+["\'][^>]*>/i', $html, $liensExternes);
        
        $externesAvecIndication = 0;
        foreach ($liensExternes[0] as $lien) {
            if (preg_match('/target=["\']_blank["\'].*rel=["\'][^"\']*noopener/i', $lien)
                || preg_match('/aria-label.*externe|title.*externe/i', $lien)) {
                $externesAvecIndication++;
            }
        }
        
        $totalExternes = count($liensExternes[0]);
        if ($totalExternes > 0) {
            $this->assertTrue($externesAvecIndication >= $totalExternes * 0.5,
                "Liens externes avec indication ($externesAvecIndication/$totalExternes)");
        }
        
        // Boutons vs liens
        $this->afficherSousSection("Distinction boutons/liens");
        
        // Liens qui ressemblent à des boutons (avec onclick)
        preg_match_all('/<a[^>]*onclick[^>]*>/i', $html, $liensBoutons);
        $this->assertTrue(count($liensBoutons[0]) < 3, 
            "Éviter onclick sur liens (utiliser <button>)",
            count($liensBoutons[0]) . " trouvés");
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 6. CONTRASTE DES COULEURS
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerContraste(): void {
        $this->afficherSection("6. CONTRASTE DES COULEURS");
        
        // Analyser les fichiers CSS
        $fichiersCSS = glob($this->cheminPublic . '/assets/css/**/*.css');
        
        $couleursTexte = [];
        $couleursFond = [];
        
        foreach ($fichiersCSS as $fichier) {
            $css = $this->lireFichier($fichier);
            
            // Extraire les couleurs de texte
            preg_match_all('/(?:^|[^-])color\s*:\s*([^;]+)/i', $css, $matches);
            $couleursTexte = array_merge($couleursTexte, $matches[1]);
            
            // Extraire les couleurs de fond
            preg_match_all('/background(?:-color)?\s*:\s*([^;]+)/i', $css, $matches);
            $couleursFond = array_merge($couleursFond, $matches[1]);
        }
        
        $this->afficherInfo("Couleurs de texte trouvées: " . count(array_unique($couleursTexte)));
        $this->afficherInfo("Couleurs de fond trouvées: " . count(array_unique($couleursFond)));
        
        // Vérifier les combinaisons problématiques courantes
        $combinaisonsProblematiques = [
            ['#ccc', '#fff'],
            ['#999', '#fff'],
            ['#aaa', '#fff'],
            ['gray', 'white'],
            ['lightgray', 'white']
        ];
        
        $problemes = 0;
        foreach ($combinaisonsProblematiques as [$texte, $fond]) {
            $texteTrouve = false;
            $fondTrouve = false;
            
            foreach ($couleursTexte as $c) {
                if (stripos($c, $texte) !== false) $texteTrouve = true;
            }
            foreach ($couleursFond as $c) {
                if (stripos($c, $fond) !== false) $fondTrouve = true;
            }
            
            if ($texteTrouve && $fondTrouve) {
                $problemes++;
            }
        }
        
        $this->assertTrue($problemes < 3, 
            "Combinaisons de contraste potentiellement problématiques",
            "$problemes détectées");
        
        // Vérifier focus visible
        $this->afficherSousSection("Focus visible");
        
        $focusStyle = false;
        foreach ($fichiersCSS as $fichier) {
            $css = $this->lireFichier($fichier);
            if (preg_match('/:focus\s*{[^}]*outline|:focus-visible\s*{/i', $css)) {
                $focusStyle = true;
                break;
            }
        }
        
        $this->assertTrue($focusStyle, "Styles de focus définis", "", Criticite::IMPORTANT);
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 7. NAVIGATION
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerNavigation(): void {
        $this->afficherSection("7. NAVIGATION");
        
        $response = $this->httpGet($this->baseUrl);
        $html = $response['body'];
        
        // Skip link
        $this->afficherSousSection("Lien d'évitement");
        
        $skipLink = preg_match('/skip|passer|aller.*contenu/i', $html);
        $this->assertTrue($skipLink, "Lien 'Skip to content' présent", "", Criticite::IMPORTANT);
        
        // Navigation cohérente
        $this->afficherSousSection("Cohérence de navigation");
        
        $pages = ['', 'galerie', 'faq', 'contact'];
        $navsHTML = [];
        
        foreach ($pages as $page) {
            $response = $this->httpGet($this->baseUrl . $page);
            preg_match('/<nav[^>]*>(.*?)<\/nav>/is', $response['body'], $nav);
            if (isset($nav[1])) {
                // Extraire les liens de navigation
                preg_match_all('/href=["\']([^"\']+)["\']/', $nav[1], $liens);
                $navsHTML[$page] = $liens[1];
            }
        }
        
        // Vérifier que la navigation est similaire sur toutes les pages
        if (count($navsHTML) >= 2) {
            $premiere = reset($navsHTML);
            $coherente = true;
            
            foreach ($navsHTML as $nav) {
                if (count(array_diff($premiere, $nav)) > 2) {
                    $coherente = false;
                    break;
                }
            }
            
            $this->assertTrue($coherente, "Navigation cohérente entre les pages");
        }
        
        // Breadcrumb
        $this->afficherSousSection("Fil d'Ariane");
        
        $breadcrumb = preg_match('/breadcrumb|fil.*ariane|aria-label=["\'].*breadcrumb/i', $html);
        $this->afficherInfo("Fil d'Ariane: " . ($breadcrumb ? "présent" : "absent"));
        
        // Tabindex
        $this->afficherSousSection("Ordre de tabulation");
        
        preg_match_all('/tabindex=["\'](\d+)["\']/', $html, $tabindexes);
        $tabindexPositifs = array_filter($tabindexes[1], fn($t) => $t > 0);
        
        $this->assertTrue(empty($tabindexPositifs), 
            "Pas de tabindex positifs (ordre naturel)",
            count($tabindexPositifs) . " trouvés");
    }
    
    // ════════════════════════════════════════════════════════════════════════
    // 8. RESPONSIVE ET ZOOM
    // ════════════════════════════════════════════════════════════════════════
    
    private function testerResponsive(): void {
        $this->afficherSection("8. RESPONSIVE ET ZOOM");
        
        // Meta viewport
        $response = $this->httpGet($this->baseUrl);
        $html = $response['body'];
        
        $viewportCorrect = preg_match('/viewport[^>]*width=device-width/i', $html);
        $this->assertTrue($viewportCorrect, "Viewport width=device-width", "", Criticite::IMPORTANT);
        
        // Pas de maximum-scale=1
        $pasMaxScale = !preg_match('/maximum-scale\s*=\s*1[^0-9]/i', $html);
        $this->assertTrue($pasMaxScale, "Zoom non bloqué (pas de maximum-scale=1)", "", Criticite::IMPORTANT);
        
        // Pas de user-scalable=no
        $pasUserScalable = !preg_match('/user-scalable\s*=\s*no/i', $html);
        $this->assertTrue($pasUserScalable, "Zoom autorisé (pas de user-scalable=no)", "", Criticite::IMPORTANT);
        
        // Media queries dans CSS
        $this->afficherSousSection("Media queries");
        
        $fichiersCSS = glob($this->cheminPublic . '/assets/css/**/*.css');
        $mediaQueries = 0;
        
        foreach ($fichiersCSS as $fichier) {
            $css = $this->lireFichier($fichier);
            $mediaQueries += preg_match_all('/@media/i', $css);
        }
        
        $this->assertTrue($mediaQueries >= 5, 
            "Media queries pour responsive ($mediaQueries trouvées)");
        
        // Unités relatives
        $this->afficherSousSection("Unités accessibles");
        
        $unitesAbsolues = 0;
        $unitesRelatives = 0;
        
        foreach ($fichiersCSS as $fichier) {
            $css = $this->lireFichier($fichier);
            
            // Compter px pour font-size (problématique)
            $unitesAbsolues += preg_match_all('/font-size\s*:\s*\d+px/i', $css);
            
            // Compter em/rem pour font-size (bon)
            $unitesRelatives += preg_match_all('/font-size\s*:\s*[\d.]+(?:em|rem|%)/i', $css);
        }
        
        $this->afficherInfo("Font-size en px: $unitesAbsolues, en em/rem/%: $unitesRelatives");
        $this->assertTrue($unitesRelatives >= $unitesAbsolues * 0.5, 
            "Utilisation d'unités relatives pour font-size");
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXÉCUTION DIRECTE
// ═══════════════════════════════════════════════════════════════════════════

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['argv'][0] ?? '')) {
    $tests = new TestsAccessibilite();
    $resultats = $tests->executer();
    exit($resultats['echoues'] > 0 ? 1 : 0);
}
