<!-- 
Page de la galerie de véhicules - Affichage, recherche et ajout de véhicules
Cette page combine la liste des véhicules avec le formulaire d'ajout
-->

<section id="galerie" class="section section--liste">
    <div class="conteneur disposition-galerie">
        <!-- Colonne de gauche : Filtres -->
        <aside class="barre-laterale-filtres">
            <h2 class="titre-filtres">Filtres sélectionnées :</h2>
            <form id="formulaire-filtres" class="formulaire-filtres">
                <!-- Type de véhicule -->
                <div class="groupe-filtre">
                    <button type="button" class="entete-filtre" aria-expanded="true">
                        Type de véhicule <span class="icone-filtre">›</span>
                    </button>
                    <div class="contenu-filtre">
                        <select id="filtre-type" name="type" class="selecteur selection-filtre">
                            <option value="">Tous les types</option>
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
            <div class="barre-tri">
                <label for="selection-tri" class="etiquette-tri">Trier par :</label>
                <select id="selection-tri" class="selecteur selection-tri">
                    <option value="recent">Les plus pertinents</option>
                    <option value="prix-croissant">Prix croissant</option>
                    <option value="prix-decroissant">Prix décroissant</option>
                    <option value="annee-decroissante">Année décroissante</option>
                    <option value="annee-croissante">Année croissante</option>
                </select>
            </div>

            <ul id="liste-vehicules" class="cartes-horizontales" aria-live="polite" aria-busy="false"></ul>
            
            <div id="etat-vide" class="vide" hidden>
                Aucun véhicule trouvé pour votre recherche.
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

