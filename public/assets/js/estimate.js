// Gestion de l'estimation de prix

const formEstimate = document.getElementById('estimate-form');
const resultCard = document.getElementById('estimate-result');
const priceValue = document.getElementById('price-value');
const btnNewEstimate = document.getElementById('btn-new-estimate');
const btnSubmit = document.getElementById('btn-estimate');
const loader = btnSubmit?.querySelector('.loader');
const btnText = btnSubmit?.querySelector('.button__text');
const messages = document.querySelector('.form__messages');

// URL de l'API d'estimation
const metaApiBase = document.querySelector('meta[name="api-base"]');
const API_ESTIMATE_URL = (metaApiBase ? metaApiBase.getAttribute('content') : './api') + '/estimate.php';

function setLoading(isLoading) {
  if (btnSubmit) {
    btnSubmit.disabled = isLoading;
    if (loader) loader.hidden = !isLoading;
    if (btnText) btnText.style.opacity = isLoading ? '0.7' : '1';
  }
}

function showMessage(text, type = 'ok') {
  if (messages) {
    if (!text) {
      messages.innerHTML = '';
      return;
    }
    messages.innerHTML = `<div class="msg msg--${type}">${text}</div>`;
  }
}

if (formEstimate) {
  formEstimate.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Récupération des données
    const formData = new FormData(formEstimate);
    const data = Object.fromEntries(formData.entries());
    
    // Validation basique côté client
    if (!data.marque || !data.modele || !data.annee) {
      showMessage('Veuillez remplir tous les champs obligatoires.', 'err');
      return;
    }

    try {
      setLoading(true);
      showMessage(''); // Effacer les messages précédents
      
      const response = await fetch(API_ESTIMATE_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || 'Une erreur est survenue lors de l\'estimation.');
      }

      if (result.prix !== null) {
        // Affichage du résultat
        priceValue.textContent = new Intl.NumberFormat('fr-FR').format(result.prix);
        formEstimate.hidden = true;
        resultCard.hidden = false;
      } else {
        showMessage(result.message || 'Estimation impossible pour ce véhicule.', 'err');
      }

    } catch (error) {
      console.error(error);
      showMessage(error.message, 'err');
    } finally {
      setLoading(false);
    }
  });
}

if (btnNewEstimate) {
  btnNewEstimate.addEventListener('click', () => {
    resultCard.hidden = true;
    formEstimate.hidden = false;
    formEstimate.reset();
    showMessage('');
  });
}
