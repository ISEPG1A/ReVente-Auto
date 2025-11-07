<!-- 
Page de la galerie de véhicules - Affichage, recherche et ajout de véhicules
Cette page combine la liste des véhicules avec le formulaire d'ajout
-->

<!-- Section de liste des véhicules avec outils de recherche et tri -->
<section id="liste" class="section section--list">
  <div class="container">
    <!-- Barre d'outils pour la recherche et le tri -->
    <div class="toolbar" role="region" aria-label="Recherche et tri">
      <!-- Champ de recherche par marque ou modèle -->
      <div class="toolbar__item">
        <label class="label" for="search">Recherche</label>
        <input id="search" class="input" type="search" 
               placeholder="Marque ou modèle" autocomplete="off" />
      </div>
      
      <!-- Sélecteur de tri des résultats -->
      <div class="toolbar__item">
        <label class="label" for="sort">Tri</label>
        <select id="sort" class="select">
          <option value="recent">Plus récents</option>
          <option value="price-asc">Prix croissant</option>
          <option value="price-desc">Prix décroissant</option>
          <option value="year-desc">Année décroissante</option>
          <option value="year-asc">Année croissante</option>
        </select>
      </div>
    </div>

    <!-- Liste des cartes de véhicules (remplie dynamiquement par JavaScript) -->
    <ul id="vehicle-list" class="cards" aria-live="polite" aria-busy="false"></ul>
    
    <!-- Message affiché quand aucun véhicule ne correspond à la recherche -->
    <div id="empty-state" class="empty" hidden>
      Aucun véhicule trouvé pour votre recherche.
    </div>
  </div>
</section>

<!-- Section du formulaire d'ajout de véhicule -->
<section id="ajout" class="section section--form">
  <div class="container">
    <h2>Ajouter un véhicule</h2>
    
    <!-- Formulaire d'ajout de véhicule (soumission gérée par JavaScript) -->
    <form id="vehicle-form" class="form" novalidate>
      <div class="grid">
        <!-- Champ marque du véhicule -->
        <div class="field">
          <label class="label" for="marque">Marque</label>
          <input id="marque" name="marque" class="input" type="text" 
                 required maxlength="50" />
          <p class="help" id="marque-help">Ex: Peugeot, Renault…</p>
        </div>
        
        <!-- Champ modèle du véhicule -->
        <div class="field">
          <label class="label" for="modele">Modèle</label>
          <input id="modele" name="modele" class="input" type="text" 
                 required maxlength="50" />
        </div>
        
        <!-- Champ année du véhicule avec validation -->
        <div class="field">
          <label class="label" for="annee">Année</label>
          <input id="annee" name="annee" class="input" type="number" 
                 required min="1900" max="2100" />
        </div>
        
        <!-- Champ prix en euros avec décimales -->
        <div class="field">
          <label class="label" for="prix">Prix (€)</label>
          <input id="prix" name="prix" class="input" type="number" 
                 required step="0.01" min="0" />
        </div>
      </div>
      
      <!-- Actions du formulaire -->
      <div class="actions">
        <!-- Bouton de soumission principal -->
        <button id="submit" class="button" type="submit">Enregistrer</button>
        
        <!-- Bouton de réinitialisation des champs -->
        <button id="btn-reset" class="button button--ghost" type="reset">Réinitialiser</button>
      </div>
      
      <!-- Zone d'affichage des messages (erreurs/succès) -->
      <div class="form__messages" aria-live="polite" role="status"></div>
    </form>
  </div>
</section>
