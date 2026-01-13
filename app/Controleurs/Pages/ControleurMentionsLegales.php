<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CONTRÔLEUR PAGE MENTIONS LÉGALES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Affiche la page des mentions légales (page statique).
 * 
 * @author  mat
 * @version 1.0
 * ═══════════════════════════════════════════════════════════════════════════
 */

class ControleurMentionsLegales extends ControleurBase
{
    public function __construct()
    {
        parent::__construct();
        $this->titrePage = 'Mentions Légales - ReVente Auto';
        $this->pageActive = 'mentions-legales';
    }

    /**
     * Affiche la page des mentions légales
     */
    public function index(): void
    {
        $this->rendu('statique/mentions-legales');
    }
}
