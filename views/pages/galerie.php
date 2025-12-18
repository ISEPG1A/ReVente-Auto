<!-- 
═══════════════════════════════════════════════════════════════════════════════
PAGE GALERIE - CATALOGUE DES VÉHICULES
Affichage des véhicules avec filtres avancés adaptés au type (voiture/moto/camion)
═══════════════════════════════════════════════════════════════════════════════
-->

<!-- Hero Section Galerie -->
<section class="hero hero--center">
    <div class="hero__background">
        <div class="hero__shapes">
            <div class="hero__shape hero__shape--1"></div>
            <div class="hero__shape hero__shape--2"></div>
        </div>
    </div>
    
    <div class="hero__content">
        <h1 class="hero__title">Trouvez votre <span class="hero__highlight">véhicule idéal</span></h1>
        <p class="hero__description">Parcourez notre sélection de véhicules d'occasion vérifiés et certifiés</p>
        
        <!-- Barre de recherche rapide -->
        <div class="galerie-hero__recherche">
            <div class="recherche-rapide">
                <i class="fas fa-search recherche-rapide__icone"></i>
                <input type="text" id="recherche-rapide" class="recherche-rapide__input" placeholder="Rechercher une marque, un modèle...">
            </div>
        </div>
        
        <!-- Sélection rapide du type de véhicule -->
        <div class="galerie-hero__types">
            <button type="button" class="type-btn type-btn--active" data-type="">
                <i class="fas fa-th-large"></i>
                <span>Tous</span>
            </button>
            <button type="button" class="type-btn" data-type="voiture">
                <i class="fas fa-car"></i>
                <span>Voitures</span>
            </button>
            <button type="button" class="type-btn" data-type="moto">
                <i class="fas fa-motorcycle"></i>
                <span>Motos</span>
            </button>
            <button type="button" class="type-btn" data-type="camion">
                <i class="fas fa-truck"></i>
                <span>Camions</span>
            </button>
        </div>
        
        <!-- Stats rapides -->
        <div class="galerie-hero__stats">
            <div class="galerie-stat">
                <i class="fas fa-car"></i>
                <span id="stat-total-vehicules">0</span> véhicules
            </div>
            <div class="galerie-stat">
                <i class="fas fa-tag"></i>
                <span>Prix vérifiés</span>
            </div>
            <div class="galerie-stat">
                <i class="fas fa-shield-alt"></i>
                <span>Qualité garantie</span>
            </div>
        </div>
    </div>
</section>

<section id="galerie" class="section section--liste">
    <div class="conteneur disposition-galerie">
        
        <!-- ═══════════════════════════════════════════════════════════════
             COLONNE GAUCHE : FILTRES AVANCÉS
             ═══════════════════════════════════════════════════════════════ -->
        <aside class="barre-laterale-filtres">
            <div class="filtres-header">
                <h2 class="titre-filtres"><i class="fas fa-sliders-h"></i> Filtres</h2>
                <button type="button" id="reset-filtres" class="bouton-reset-filtres">
                    <i class="fas fa-undo"></i> Réinitialiser
                </button>
            </div>
            
            <!-- Bouton toggle filtres mobile -->
            <button type="button" id="toggle-filtres-mobile" class="toggle-filtres-mobile">
                <i class="fas fa-filter"></i> Afficher les filtres
            </button>
            
            <form id="formulaire-filtres" class="formulaire-filtres">
                
                <!-- ═══ TYPE DE VÉHICULE ═══ -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-car-side"></i> Type de véhicule</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre" hidden>
                        <select id="filtre-type" name="type" class="selecteur selection-filtre">
                            <option value="">Tous les types</option>
                            <option value="voiture">🚗 Voiture</option>
                            <option value="moto">🏍️ Moto</option>
                            <option value="camion">🚛 Camion</option>
                        </select>
                    </div>
                </div>

                <!-- ═══ MARQUE ═══ -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-industry"></i> Marque</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre" hidden>
                        <select id="filtre-marque" name="marque" class="selecteur selection-filtre">
                            <option value="">Toutes les marques</option>
                        </select>
                    </div>
                </div>

                <!-- ═══ PRIX ═══ -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-euro-sign"></i> Prix</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre" hidden>
                        <div class="champs-plage">
                            <input type="number" id="filtre-prix-min" name="prix_min" class="saisie champ-plage" placeholder="Min €" min="0">
                            <span class="plage-separateur">à</span>
                            <input type="number" id="filtre-prix-max" name="prix_max" class="saisie champ-plage" placeholder="Max €" min="0">
                        </div>
                        <!-- Raccourcis prix -->
                        <div class="raccourcis-prix">
                            <button type="button" class="raccourci-btn" data-min="0" data-max="5000">< 5k€</button>
                            <button type="button" class="raccourci-btn" data-min="5000" data-max="15000">5-15k€</button>
                            <button type="button" class="raccourci-btn" data-min="15000" data-max="30000">15-30k€</button>
                            <button type="button" class="raccourci-btn" data-min="30000" data-max="">30k+</button>
                        </div>
                    </div>
                </div>

                <!-- ═══ ANNÉE ═══ -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-calendar-alt"></i> Année</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre" hidden>
                        <div class="champs-plage">
                            <input type="number" id="filtre-annee-min" name="annee_min" class="saisie champ-plage" placeholder="Min" min="1950" max="2025">
                            <span class="plage-separateur">à</span>
                            <input type="number" id="filtre-annee-max" name="annee_max" class="saisie champ-plage" placeholder="Max" min="1950" max="2025">
                        </div>
                    </div>
                </div>

                <!-- ═══ KILOMÉTRAGE ═══ -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-tachometer-alt"></i> Kilométrage</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre" hidden>
                        <div class="champs-plage">
                            <input type="number" id="filtre-km-min" name="km_min" class="saisie champ-plage" placeholder="Min km" min="0">
                            <span class="plage-separateur">à</span>
                            <input type="number" id="filtre-km-max" name="km_max" class="saisie champ-plage" placeholder="Max km" min="0">
                        </div>
                        <!-- Raccourcis km -->
                        <div class="raccourcis-prix">
                            <button type="button" class="raccourci-km-btn" data-min="0" data-max="50000">< 50k</button>
                            <button type="button" class="raccourci-km-btn" data-min="50000" data-max="100000">50-100k</button>
                            <button type="button" class="raccourci-km-btn" data-min="100000" data-max="">100k+</button>
                        </div>
                    </div>
                </div>

                <!-- ═══ CARBURANT ═══ -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-gas-pump"></i> Carburant</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre liste-cases-a-cocher" hidden>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="carburant" value="Essence"> 
                            <span class="case-texte">🔴 Essence</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="carburant" value="Diesel"> 
                            <span class="case-texte">⚫ Diesel</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="carburant" value="Électrique"> 
                            <span class="case-texte">🔵 Électrique</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="carburant" value="Hybride"> 
                            <span class="case-texte">🟢 Hybride</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="carburant" value="GPL"> 
                            <span class="case-texte">🟡 GPL</span>
                        </label>
                    </div>
                </div>

                <!-- ═══ BOÎTESE DE VITESSE (masqué pour motos) ═══ -->
                <div class="groupe-filtre" id="groupe-filtre-boite">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-cog"></i> Boîte de vitesse</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre liste-cases-a-cocher" hidden>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="boite" value="Manuelle"> 
                            <span class="case-texte">⚙️ Manuelle</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="boite" value="Automatique"> 
                            <span class="case-texte">🅰️ Automatique</span>
                        </label>
                    </div>
                </div>

                <!-- ═══ ÉTAT DU VÉHICULE ═══ -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-star-half-alt"></i> État</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre liste-cases-a-cocher" hidden>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="etat" value="neuf"> 
                            <span class="case-texte">✨ Neuf</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="etat" value="bon"> 
                            <span class="case-texte">👍 Bon état</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="etat" value="moyen"> 
                            <span class="case-texte">👌 État moyen</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="etat" value="mauvais"> 
                            <span class="case-texte">👎 Mauvais état</span>
                        </label>
                    </div>
                </div>

                <!-- ═══ VIGNETTE CRIT'AIR ═══ -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-wind"></i> Crit'Air</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre liste-cases-a-cocher" hidden>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="crit_air" value="0"> 
                            <span class="case-texte">🟢 Crit'Air 0</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="crit_air" value="1"> 
                            <span class="case-texte">🟣 Crit'Air 1</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="crit_air" value="2"> 
                            <span class="case-texte">🟡 Crit'Air 2</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="crit_air" value="3"> 
                            <span class="case-texte">🟠 Crit'Air 3</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="crit_air" value="4"> 
                            <span class="case-texte">🟤 Crit'Air 4</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="crit_air" value="5"> 
                            <span class="case-texte">⚫ Crit'Air 5</span>
                        </label>
                    </div>
                </div>

                <!-- ═══ NOMBRE DE PORTES (voitures seulement) ═══ -->
                <div class="groupe-filtre" id="groupe-filtre-portes" style="display: none;">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-door-open"></i> Nombre de portes</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre liste-cases-a-cocher" hidden>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="nb_portes" value="2"> 
                            <span class="case-texte">2 portes</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="nb_portes" value="3"> 
                            <span class="case-texte">3 portes</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="nb_portes" value="4"> 
                            <span class="case-texte">4 portes</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="nb_portes" value="5"> 
                            <span class="case-texte">5 portes</span>
                        </label>
                    </div>
                </div>

                <!-- ═══ CONTRÔLE TECHNIQUE (voitures/camions) ═══ -->
                <div class="groupe-filtre" id="groupe-filtre-ct">
                    <button type="button" class="entete-filtre" aria-expanded="false">
                        <span><i class="fas fa-clipboard-check"></i> Contrôle technique</span>
                        <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre liste-cases-a-cocher" hidden>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="controle_technique" value="oui"> 
                            <span class="case-texte">✅ À jour</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="controle_technique" value="non"> 
                            <span class="case-texte">❌ À faire</span>
                        </label>
                        <label class="etiquette-case-a-cocher">
                            <input type="checkbox" name="controle_technique" value="non_requis"> 
                            <span class="case-texte">🔘 Non requis</span>
                        </label>
                    </div>
                </div>

                <button type="button" id="bouton-appliquer-filtres" class="bouton bouton-filtre">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </form>
        </aside>

        <!-- ═══════════════════════════════════════════════════════════════
             COLONNE DROITE : LISTE DES VÉHICULES
             ═══════════════════════════════════════════════════════════════ -->
        <div class="contenu-galerie">
            <!-- Barre d'outils -->
            <div class="barre-outils-galerie">
                <div class="resultats-info">
                    <span id="nombre-resultats">0</span> véhicule(s) trouvé(s)
                </div>
                <div class="barre-tri">
                    <label for="selection-tri" class="etiquette-tri">Trier par :</label>
                    <select id="selection-tri" class="selecteur selection-tri">
                        <option value="recent">📅 Plus récents</option>
                        <option value="score-ia">🤖 Score IA (meilleurs en premier)</option>
                        <option value="prix-croissant">💰 Prix croissant</option>
                        <option value="prix-decroissant">💰 Prix décroissant</option>
                        <option value="km-croissant">🛣️ Km croissant</option>
                        <option value="km-decroissant">🛣️ Km décroissant</option>
                        <option value="annee-decroissante">📆 Année décroissante</option>
                        <option value="annee-croissante">📆 Année croissante</option>
                    </select>
                </div>
            </div>

            <!-- Filtres actifs (tags) -->
            <div id="filtres-actifs" class="filtres-actifs" hidden>
                <span class="filtres-actifs__label">Filtres actifs :</span>
                <div id="tags-filtres" class="tags-filtres"></div>
                <button type="button" id="effacer-tous-filtres" class="effacer-filtres-btn">
                    <i class="fas fa-times"></i> Tout effacer
                </button>
            </div>

            <!-- Grille des véhicules -->
            <ul id="liste-vehicules" class="grille-vehicules" aria-live="polite" aria-busy="false"></ul>
            
            <!-- État vide -->
            <div id="etat-vide" class="etat-vide" hidden>
                <div class="etat-vide__icone">
                    <i class="fas fa-car-side"></i>
                </div>
                <h3 class="etat-vide__titre">Aucun véhicule trouvé</h3>
                <p class="etat-vide__message">Essayez de modifier vos filtres ou votre recherche</p>
                <button type="button" class="bouton bouton--secondaire" onclick="document.getElementById('reset-filtres').click()">
                    <i class="fas fa-undo"></i> Réinitialiser les filtres
                </button>
            </div>
            
            <!-- Loader -->
            <div id="loader-galerie" class="loader-galerie" hidden>
                <div class="loader-spinner"></div>
                <p>Chargement des véhicules...</p>
            </div>
        </div>
    </div>
</section>

<script type="module">
    import VueGalerie from './assets/js/Vehicule/Galerie/VueGalerie.js?v=20251217164000';
    document.addEventListener('DOMContentLoaded', () => {
        const galerie = new VueGalerie();
        galerie.initialiser();
    });
</script>

