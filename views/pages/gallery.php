<section id="liste" class="section section--list">
  <div class="container">
    <div class="toolbar" role="region" aria-label="Recherche et tri">
      <div class="toolbar__item">
        <label class="label" for="search">Recherche</label>
        <input id="search" class="input" type="search" placeholder="Marque ou modèle" autocomplete="off" />
      </div>
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

    <ul id="vehicle-list" class="cards" aria-live="polite" aria-busy="false"></ul>
    <div id="empty-state" class="empty" hidden>Aucun véhicule trouvé pour votre recherche.</div>
  </div>
</section>

<section id="ajout" class="section section--form">
  <div class="container">
    <h2>Ajouter un véhicule</h2>
    <form id="vehicle-form" class="form" novalidate>
      <div class="grid">
        <div class="field">
          <label class="label" for="marque">Marque</label>
          <input id="marque" name="marque" class="input" type="text" required maxlength="50" />
          <p class="help" id="marque-help">Ex: Peugeot, Renault…</p>
        </div>
        <div class="field">
          <label class="label" for="modele">Modèle</label>
          <input id="modele" name="modele" class="input" type="text" required maxlength="50" />
        </div>
        <div class="field">
          <label class="label" for="annee">Année</label>
          <input id="annee" name="annee" class="input" type="number" required min="1900" max="2100" />
        </div>
        <div class="field">
          <label class="label" for="prix">Prix (€)</label>
          <input id="prix" name="prix" class="input" type="number" required step="0.01" min="0" />
        </div>
      </div>
      <div class="actions">
        <button id="submit" class="button" type="submit">Enregistrer</button>
        <button id="btn-reset" class="button button--ghost" type="reset">Réinitialiser</button>
      </div>
      <div class="form__messages" aria-live="polite" role="status"></div>
    </form>
  </div>
</section>
