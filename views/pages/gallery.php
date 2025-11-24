<!-- 
Page de la galerie de véhicules - Affichage, recherche et ajout de véhicules
Cette page combine la liste des véhicules avec le formulaire d'ajout
-->

<section id="galerie" class="section section--liste">
  <div class="container gallery-layout">
    
    <!-- Colonne de gauche : Filtres -->
    <aside class="filters-sidebar">
      <h2 class="filters-title">Filtres sélectionnées :</h2>
      
      <form id="filters-form" class="filters-form">
        <!-- Type de véhicule -->
        <div class="filter-group">
          <button type="button" class="filter-header" aria-expanded="true">
            Type de véhicule
            <span class="filter-icon">›</span>
          </button>
          <div class="filter-content">
            <select id="filter-type" name="type" class="selecteur filter-select">
              <option value="">Tous les types</option>
              <option value="Berline">Berline</option>
              <option value="SUV">SUV</option>
              <option value="Citadine">Citadine</option>
              <option value="Utilitaire">Utilitaire</option>
            </select>
          </div>
        </div>

        <!-- Marque -->
        <div class="filter-group">
          <button type="button" class="filter-header" aria-expanded="true">
            Marque
            <span class="filter-icon">›</span>
          </button>
          <div class="filter-content">
            <input type="text" id="filter-marque" name="marque" class="saisie filter-input" placeholder="Toutes les marques">
          </div>
        </div>

        <!-- Modèle -->
        <div class="filter-group">
          <button type="button" class="filter-header" aria-expanded="true">
            Modèle
            <span class="filter-icon">›</span>
          </button>
          <div class="filter-content">
            <input type="text" id="filter-modele" name="modele" class="saisie filter-input" placeholder="Tous les modèles">
          </div>
        </div>

        <!-- Année -->
        <div class="filter-group">
          <button type="button" class="filter-header" aria-expanded="true">
            Année
            <span class="filter-icon">›</span>
          </button>
          <div class="filter-content">
            <div class="range-inputs">
              <input type="number" id="filter-annee-min" name="annee_min" class="saisie range-input" placeholder="Min">
              <input type="number" id="filter-annee-max" name="annee_max" class="saisie range-input" placeholder="Max">
            </div>
          </div>
        </div>

        <!-- Kilométrage -->
        <div class="filter-group">
          <button type="button" class="filter-header" aria-expanded="true">
            Kilométrage
            <span class="filter-icon">›</span>
          </button>
          <div class="filter-content">
            <div class="range-inputs">
              <input type="number" id="filter-km-min" name="km_min" class="saisie range-input" placeholder="Min km">
              <input type="number" id="filter-km-max" name="km_max" class="saisie range-input" placeholder="Max km">
            </div>
          </div>
        </div>

        <!-- Carburant -->
        <div class="filter-group">
          <button type="button" class="filter-header" aria-expanded="true">
            Carburant
            <span class="filter-icon">›</span>
          </button>
          <div class="filter-content checkbox-list">
            <label class="checkbox-label">
              <input type="checkbox" name="carburant" value="Diesel"> Diesel
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="carburant" value="Essence"> Essence
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="carburant" value="Électrique"> Électrique
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="carburant" value="Hybride"> Hybride
            </label>
          </div>
        </div>

        <!-- Boite de vitesse -->
        <div class="filter-group">
          <button type="button" class="filter-header" aria-expanded="true">
            Boite de vitesse
            <span class="filter-icon">›</span>
          </button>
          <div class="filter-content checkbox-list">
            <label class="checkbox-label">
              <input type="checkbox" name="boite" value="Manuelle"> Manuelle
            </label>
            <label class="checkbox-label">
              <input type="checkbox" name="boite" value="Automatique"> Automatique
            </label>
          </div>
        </div>

        <!-- Prix -->
        <div class="filter-group">
          <button type="button" class="filter-header" aria-expanded="true">
            Prix
            <span class="filter-icon">›</span>
          </button>
          <div class="filter-content">
            <div class="range-inputs">
              <input type="number" id="filter-prix-min" name="prix_min" class="saisie range-input" placeholder="Min €">
              <input type="number" id="filter-prix-max" name="prix_max" class="saisie range-input" placeholder="Max €">
            </div>
          </div>
        </div>

        <button type="button" id="btn-apply-filters" class="bouton bouton-filtre">Rechercher</button>
      </form>
    </aside>

    <!-- Colonne de droite : Liste des véhicules -->
    <div class="gallery-content">
      <!-- Barre de tri -->
      <div class="sort-bar">
        <label for="sort-select" class="sort-label">Trier par :</label>
        <select id="sort-select" class="selecteur sort-select">
          <option value="recent">Les plus pertinents</option>
          <option value="price-asc">Prix croissant</option>
          <option value="price-desc">Prix décroissant</option>
          <option value="year-desc">Année décroissante</option>
          <option value="year-asc">Année croissante</option>
        </select>
      </div>

      <!-- Liste des cartes de véhicules -->
      <ul id="vehicle-list" class="cards-horizontal" aria-live="polite" aria-busy="false"></ul>
      
      <!-- Message affiché quand aucun véhicule ne correspond à la recherche -->
      <div id="empty-state" class="vide" hidden>
        Aucun véhicule trouvé pour votre recherche.
      </div>
    </div>

  </div>
</section>

