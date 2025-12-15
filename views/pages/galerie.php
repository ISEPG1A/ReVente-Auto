<!-- 
Page de la galerie de véhicules - Affichage, recherche et ajout de véhicules
Cette page combine la liste des véhicules avec le formulaire d'ajout
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
                <span>Garantie incluse</span>
            </div>
        </div>
    </div>
</section>

<section id="galerie" class="section section--liste">
    <div class="conteneur disposition-galerie">
        <!-- Colonne de gauche : Filtres -->
        <aside class="barre-laterale-filtres">
            <div class="filtres-header">
                <h2 class="titre-filtres"><i class="fas fa-sliders-h"></i> Filtres</h2>
                <button type="button" id="reset-filtres" class="bouton-reset-filtres">
                    <i class="fas fa-undo"></i> Réinitialiser
                </button>
            </div>
            <form id="formulaire-filtres" class="formulaire-filtres">
                <!-- Type de véhicule -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="true">
                        Type de véhicule <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre">
                        <select id="filtre-type" name="type" class="selecteur selection-filtre">
                            <option value="tous">Tous les types</option>
                            <option value="Berline">Berline</option>
                            <option value="SUV">SUV</option>
                            <option value="Citadine">Citadine</option>
                            <option value="Utilitaire">Utilitaire</option>
                        </select>
                    </div>
                </div>

                <!-- Marque -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="true">
                        Marque <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre">
                        <select id="filtre-marque" name="marque" class="selecteur selection-filtre">
                            <option value="toutes">Toutes les marques</option>
                        </select>
                    </div>
                </div>

                <!-- Année -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="true">
                        Année <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre">
                        <div class="champs-plage">
                            <input type="number" id="filtre-annee-min" name="annee_min" class="saisie champ-plage" placeholder="Min">
                            <input type="number" id="filtre-annee-max" name="annee_max" class="saisie champ-plage" placeholder="Max">
                        </div>
                    </div>
                </div>

                <!-- Prix -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="true">
                        Prix <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre">
                        <div class="champs-plage">
                            <input type="number" id="filtre-prix-min" name="prix_min" class="saisie champ-plage" placeholder="Min €">
                            <input type="number" id="filtre-prix-max" name="prix_max" class="saisie champ-plage" placeholder="Max €">
                        </div>
                    </div>
                </div>

                <!-- Carburant -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="true">
                        Carburant <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre liste-cases-a-cocher">
                        <label class="etiquette-case-a-cocher"><input type="checkbox" name="carburant" value="Diesel"> Diesel</label>
                        <label class="etiquette-case-a-cocher"><input type="checkbox" name="carburant" value="Essence"> Essence</label>
                        <label class="etiquette-case-a-cocher"><input type="checkbox" name="carburant" value="Électrique"> Électrique</label>
                        <label class="etiquette-case-a-cocher"><input type="checkbox" name="carburant" value="Hybride"> Hybride</label>
                    </div>
                </div>

                <!-- Boite -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="true">
                        Boite de vitesse <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre liste-cases-a-cocher">
                        <label class="etiquette-case-a-cocher"><input type="checkbox" name="boite" value="Manuelle"> Manuelle</label>
                        <label class="etiquette-case-a-cocher"><input type="checkbox" name="boite" value="Automatique"> Automatique</label>
                    </div>
                </div>

                <button type="button" id="bouton-appliquer-filtres" class="bouton bouton-filtre">Rechercher</button>
            </form>
        </aside>

        <!-- Colonne de droite : Liste des véhicules -->
        <div class="contenu-galerie">
            <div class="barre-outils-galerie">
                <div class="resultats-info">
                    <span id="nombre-resultats">0</span> véhicules trouvés
                </div>
                <div class="barre-tri">
                    <label for="selection-tri" class="etiquette-tri">Trier par :</label>
                    <select id="selection-tri" class="selecteur selection-tri">
                        <option value="recent">Les plus récents</option>
                        <option value="prix-croissant">Prix croissant</option>
                    <option value="prix-decroissant">Prix décroissant</option>
                    <option value="annee-decroissante">Année décroissante</option>
                    <option value="annee-croissante">Année croissante</option>
                </select>
                </div>
            </div>

            <ul id="liste-vehicules" class="grille-vehicules" aria-live="polite" aria-busy="false"></ul>
            
            <div id="etat-vide" class="etat-vide" hidden>
                <div class="etat-vide__icone">
                    <i class="fas fa-car-side"></i>
                </div>
                <h3 class="etat-vide__titre">Aucun véhicule trouvé</h3>
                <p class="etat-vide__message">Essayez de modifier vos filtres ou votre recherche</p>
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
    import VueGalerie from './assets/js/Vehicule/Galerie/VueGalerie.js';
    document.addEventListener('DOMContentLoaded', () => {
        const galerie = new VueGalerie();
        galerie.initialiser();
    });
</script>

