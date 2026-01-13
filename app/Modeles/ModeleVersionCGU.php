<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * MODÈLE VERSION CGU
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Gère le versioning automatique des CGU avec génération de PDF.
 * Chaque modification des CGU entraîne la création d'une nouvelle version archivée.
 * 
 * @author  ReVente Auto
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */
class ModeleVersionCGU {
    
    private $db;
    private $cheminVersions;
    
    public function __construct() {
        $this->db = BaseDeDonnees::obtenirConnexion();
        $this->cheminVersions = dirname(__DIR__, 2) . '/public/uploads/cgu_versions/';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->cheminVersions)) {
            mkdir($this->cheminVersions, 0755, true);
        }
    }
    
    /**
     * Récupère toutes les versions des CGU
     * 
     * @return array Liste des versions triées par date décroissante
     */
    public function obtenirToutesVersions(): array {
        $stmt = $this->db->query("
            SELECT id, date_creation, hash_contenu, nom_fichier, taille_fichier
            FROM cgu_versions
            ORDER BY date_creation DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère la dernière version des CGU
     * 
     * @return array|null La dernière version ou null
     */
    public function obtenirDerniereVersion(): ?array {
        $stmt = $this->db->query("
            SELECT id, date_creation, hash_contenu, nom_fichier
            FROM cgu_versions
            ORDER BY date_creation DESC
            LIMIT 1
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Génère un hash du contenu actuel des CGU
     * Permet de détecter si le contenu a changé
     * 
     * @return string Hash SHA256 du contenu
     */
    public function genererHashContenu(): string {
        // Récupérer tout le contenu des CGU de manière ordonnée
        $contenu = $this->obtenirContenuComplet();
        return hash('sha256', $contenu);
    }
    
    /**
     * Récupère le contenu complet des CGU sous forme de texte
     * 
     * @return string Contenu formaté des CGU
     */
    public function obtenirContenuComplet(): string {
        $contenu = '';
        
        // Récupérer les articles publiés
        $stmt = $this->db->query("
            SELECT id, numero, titre
            FROM cgu_articles
            WHERE statut = 'publie'
            ORDER BY numero ASC
        ");
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($articles as $article) {
            $contenu .= "ARTICLE {$article['numero']} - {$article['titre']}\n";
            $contenu .= str_repeat('=', 50) . "\n\n";
            
            // Sections
            $stmtSections = $this->db->prepare("
                SELECT id, numero, titre, contenu
                FROM cgu_sections
                WHERE article_id = ?
                ORDER BY numero ASC
            ");
            $stmtSections->execute([$article['id']]);
            $sections = $stmtSections->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($sections as $section) {
                $contenu .= "{$article['numero']}.{$section['numero']} {$section['titre']}\n";
                if (!empty($section['contenu'])) {
                    $contenu .= $section['contenu'] . "\n";
                }
                $contenu .= "\n";
                
                // Points
                $stmtPoints = $this->db->prepare("
                    SELECT numero, titre, contenu
                    FROM cgu_points
                    WHERE section_id = ?
                    ORDER BY numero ASC
                ");
                $stmtPoints->execute([$section['id']]);
                $points = $stmtPoints->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($points as $point) {
                    $contenu .= "  {$article['numero']}.{$section['numero']}.{$point['numero']}";
                    if (!empty($point['titre'])) {
                        $contenu .= " {$point['titre']}";
                    }
                    $contenu .= "\n";
                    if (!empty($point['contenu'])) {
                        $contenu .= "    {$point['contenu']}\n";
                    }
                    $contenu .= "\n";
                }
            }
            $contenu .= "\n";
        }
        
        return $contenu;
    }
    
    /**
     * Vérifie si le contenu des CGU a changé depuis la dernière version
     * 
     * @return bool True si le contenu a changé
     */
    public function contenuAChange(): bool {
        $derniereVersion = $this->obtenirDerniereVersion();
        
        if ($derniereVersion === null) {
            // Pas de version précédente = première version à créer
            return true;
        }
        
        $hashActuel = $this->genererHashContenu();
        return $hashActuel !== $derniereVersion['hash_contenu'];
    }
    
    /**
     * Crée une nouvelle version des CGU avec PDF
     * 
     * @return array|false Informations sur la version créée ou false en cas d'erreur
     */
    public function creerNouvelleVersion(): array|false {
        try {
            $hashContenu = $this->genererHashContenu();
            $dateCreation = date('Y-m-d H:i:s');
            $nomFichier = 'CGU_ReVenteAuto_' . date('Y-m-d') . '.pdf';
            
            // Générer le PDF
            $cheminPdf = $this->cheminVersions . $nomFichier;
            $this->genererPDF($cheminPdf, $dateCreation);
            
            // Taille du fichier
            $tailleFichier = filesize($cheminPdf);
            
            // Enregistrer en base
            $stmt = $this->db->prepare("
                INSERT INTO cgu_versions (date_creation, hash_contenu, nom_fichier, taille_fichier)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$dateCreation, $hashContenu, $nomFichier, $tailleFichier]);
            
            return [
                'id' => $this->db->lastInsertId(),
                'date_creation' => $dateCreation,
                'nom_fichier' => $nomFichier,
                'taille_fichier' => $tailleFichier
            ];
        } catch (Exception $e) {
            error_log("Erreur création version CGU: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Génère un PDF des CGU avec FPDF
     * 
     * @param string $cheminPdf Chemin de destination
     * @param string $date Date de création
     */
    private function genererPDF(string $cheminPdf, string $date): void {
        // Charger FPDF
        require_once dirname(__DIR__) . '/Libs/FPDF/fpdf.php';
        
        // Créer le PDF
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();
        
        // Définir les marges
        $pdf->SetMargins(15, 15, 15);
        
        $dateFormatee = (new DateTime($date))->format('d/m/Y');
        
        // ═══════════════════════════════════════════════════════════════════
        // EN-TÊTE
        // ═══════════════════════════════════════════════════════════════════
        
        // Titre principal
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->SetTextColor(249, 115, 22); // Orange
        $pdf->Cell(0, 12, $this->utf8Decode('Conditions Générales d\'Utilisation'), 0, 1, 'C');
        
        // Sous-titre
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 8, $this->utf8Decode('ReVente Auto - Plateforme de vente de véhicules d\'occasion'), 0, 1, 'C');
        
        // Ligne de séparation
        $pdf->Ln(5);
        $pdf->SetDrawColor(249, 115, 22);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(10);
        
        // Date du document
        $pdf->SetFillColor(248, 249, 250);
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 8, 'Date : ' . $dateFormatee, 0, 1, 'C', true);
        $pdf->Ln(10);
        
        // ═══════════════════════════════════════════════════════════════════
        // CONTENU
        // ═══════════════════════════════════════════════════════════════════
        
        // Récupérer les articles publiés
        $stmt = $this->db->query("
            SELECT id, numero, titre
            FROM cgu_articles
            WHERE statut = 'publie'
            ORDER BY numero ASC
        ");
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($articles as $article) {
            // Vérifier s'il faut une nouvelle page
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }
            
            // Titre de l'article
            $pdf->SetFillColor(26, 26, 46); // Fond sombre
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 12);
            $pdf->Cell(0, 10, $this->utf8Decode('Article ' . $article['numero'] . ' - ' . $article['titre']), 0, 1, 'L', true);
            $pdf->Ln(5);
            
            // Sections
            $stmtSections = $this->db->prepare("
                SELECT id, numero, titre, contenu
                FROM cgu_sections
                WHERE article_id = ?
                ORDER BY numero ASC
            ");
            $stmtSections->execute([$article['id']]);
            $sections = $stmtSections->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($sections as $section) {
                // Titre de la section
                $pdf->SetTextColor(139, 92, 246); // Violet
                $pdf->SetFont('Helvetica', 'B', 11);
                $pdf->Cell(0, 7, $this->utf8Decode($article['numero'] . '.' . $section['numero'] . ' ' . $section['titre']), 0, 1);
                
                // Contenu de la section
                if (!empty($section['contenu'])) {
                    $pdf->SetTextColor(80, 80, 80);
                    $pdf->SetFont('Helvetica', '', 10);
                    $pdf->SetX(20);
                    $pdf->MultiCell(170, 5, $this->utf8Decode($section['contenu']));
                    $pdf->Ln(3);
                }
                
                // Points
                $stmtPoints = $this->db->prepare("
                    SELECT numero, titre, contenu
                    FROM cgu_points
                    WHERE section_id = ?
                    ORDER BY numero ASC
                ");
                $stmtPoints->execute([$section['id']]);
                $points = $stmtPoints->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($points as $point) {
                    // Vérifier s'il faut une nouvelle page
                    if ($pdf->GetY() > 265) {
                        $pdf->AddPage();
                    }
                    
                    // Numéro et titre du point
                    $pdf->SetTextColor(249, 115, 22); // Orange
                    $pdf->SetFont('Helvetica', 'B', 10);
                    $pointHeader = $article['numero'] . '.' . $section['numero'] . '.' . $point['numero'];
                    if (!empty($point['titre'])) {
                        $pointHeader .= ' ' . $point['titre'];
                    }
                    $pdf->SetX(20);
                    $pdf->Cell(0, 6, $this->utf8Decode($pointHeader), 0, 1);
                    
                    // Contenu du point
                    if (!empty($point['contenu'])) {
                        $pdf->SetTextColor(80, 80, 80);
                        $pdf->SetFont('Helvetica', '', 9);
                        $pdf->SetX(25);
                        $pdf->MultiCell(165, 5, $this->utf8Decode($point['contenu']));
                    }
                    $pdf->Ln(2);
                }
                $pdf->Ln(3);
            }
            $pdf->Ln(5);
        }
        
        // ═══════════════════════════════════════════════════════════════════
        // PIED DE PAGE
        // ═══════════════════════════════════════════════════════════════════
        
        $pdf->Ln(10);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
        
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 5, $this->utf8Decode('Document généré automatiquement le ' . $dateFormatee), 0, 1, 'C');
        $pdf->Cell(0, 5, $this->utf8Decode('ReVente Auto © ' . date('Y') . ' - Tous droits réservés'), 0, 1, 'C');
        
        // Sauvegarder le PDF
        $pdf->Output('F', $cheminPdf);
    }
    
    /**
     * Convertit UTF-8 en ISO-8859-1 pour FPDF
     * 
     * @param string $text Texte en UTF-8
     * @return string Texte converti
     */
    private function utf8Decode(string $text): string {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
    }
    
    /**
     * Supprime une version des CGU (admin uniquement)
     * 
     * @param int $id ID de la version
     * @return bool Succès
     */
    public function supprimerVersion(int $id): bool {
        try {
            // Récupérer le nom du fichier
            $stmt = $this->db->prepare("SELECT nom_fichier FROM cgu_versions WHERE id = ?");
            $stmt->execute([$id]);
            $version = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$version) {
                return false;
            }
            
            // Supprimer le fichier
            $cheminFichier = $this->cheminVersions . $version['nom_fichier'];
            if (file_exists($cheminFichier)) {
                unlink($cheminFichier);
            }
            
            // Supprimer de la base
            $stmt = $this->db->prepare("DELETE FROM cgu_versions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Erreur suppression version CGU: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtient le chemin complet d'un fichier de version
     * 
     * @param string $nomFichier Nom du fichier
     * @return string|null Chemin complet ou null si inexistant
     */
    public function obtenirCheminFichier(string $nomFichier): ?string {
        $chemin = $this->cheminVersions . basename($nomFichier);
        return file_exists($chemin) ? $chemin : null;
    }
    
    /**
     * Formate la taille d'un fichier en unité lisible
     * 
     * @param int $bytes Taille en bytes
     * @return string Taille formatée
     */
    public static function formaterTaille(int $bytes): string {
        $unites = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($unites) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $unites[$i];
    }
}
