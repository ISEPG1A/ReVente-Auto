import { obtenirUrlApi } from '../app.js';

export default class VueEstimation {
    constructor() {
        // Construction de l'URL du contrôleur
        this.urlApi = obtenirUrlApi('/estimation');

        this.initialiser();
    }

    initialiser() {
        this.formulaireEstimation = document.getElementById('formulaire-estimation');
        this.carteResultat = document.getElementById('resultat-estimation');
        this.carteAttente = document.getElementById('estimation-waiting');
        this.carteErreur = document.getElementById('estimation-error');
        this.valeurPrix = document.getElementById('valeur-prix');
        this.boutonNouvelleEstimation = document.getElementById('bouton-nouvelle-estimation');
        this.boutonReessayer = document.getElementById('bouton-reessayer');
        this.boutonSoumettre = document.getElementById('bouton-estimer');
        this.chargeur = this.boutonSoumettre?.querySelector('.chargeur');
        this.texteBouton = this.boutonSoumettre?.querySelector('.texte-bouton');
        this.messages = document.querySelector('.messages-formulaire');
        
        // Nouveaux éléments
        this.resultatVehicule = document.getElementById('resultat-vehicule');
        this.prixMin = document.getElementById('prix-min');
        this.prixMax = document.getElementById('prix-max');
        this.tendanceMarche = document.getElementById('tendance-marche');
        this.tempsVente = document.getElementById('temps-vente');
        this.erreurVehicule = document.getElementById('erreur-vehicule');

        if (this.formulaireEstimation) {
            this.formulaireEstimation.addEventListener('submit', (e) => this.gererSoumission(e));
        }

        if (this.boutonNouvelleEstimation) {
            this.boutonNouvelleEstimation.addEventListener('click', () => this.reinitialiserFormulaire());
        }
        
        if (this.boutonReessayer) {
            this.boutonReessayer.addEventListener('click', () => this.reinitialiserFormulaire());
        }
    }

    definirChargement(estEnChargement) {
        if (this.boutonSoumettre) {
            this.boutonSoumettre.disabled = estEnChargement;
            if (this.chargeur) this.chargeur.hidden = !estEnChargement;
            if (this.texteBouton) this.texteBouton.style.opacity = estEnChargement ? '0.7' : '1';
        }
    }

    afficherMessage(texte, type = 'ok') {
        if (this.messages) {
            if (!texte) {
                this.messages.innerHTML = '';
                return;
            }
            this.messages.innerHTML = `<div class="msg msg--${type}">${texte}</div>`;
        }
    }

    formaterPrix(prix) {
        return new Intl.NumberFormat('fr-FR').format(prix);
    }

    async gererSoumission(e) {
        e.preventDefault();
        
        const donneesFormulaire = new FormData(this.formulaireEstimation);
        const donnees = Object.fromEntries(donneesFormulaire.entries());
        
        if (!donnees.marque || !donnees.modele || !donnees.annee) {
            this.afficherMessage('Veuillez remplir tous les champs obligatoires.', 'err');
            return;
        }

        try {
            this.definirChargement(true);
            this.afficherMessage('');
            
            const reponse = await fetch(this.urlApi, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(donnees)
            });

            const resultat = await reponse.json();

            if (!reponse.ok) {
                throw new Error(resultat.error || 'Une erreur est survenue lors de l\'estimation.');
            }

            if (resultat.prix !== null) {
                const prix = resultat.prix;
                const prixMin = Math.round(prix * 0.85);
                const prixMax = Math.round(prix * 1.15);
                
                // Afficher le véhicule
                if (this.resultatVehicule) {
                    this.resultatVehicule.textContent = `${donnees.marque} ${donnees.modele} • ${donnees.annee}`;
                }
                
                // Afficher le prix principal avec animation
                this.valeurPrix.textContent = this.formaterPrix(prix);
                
                // Afficher la fourchette de prix
                if (this.prixMin) this.prixMin.textContent = this.formaterPrix(prixMin) + ' €';
                if (this.prixMax) this.prixMax.textContent = this.formaterPrix(prixMax) + ' €';
                
                // Tendance marché - utiliser la réponse de l'IA
                if (this.tendanceMarche) {
                    const tendance = resultat.tendance || 'stable';
                    if (tendance === 'hausse') {
                        this.tendanceMarche.textContent = '📈 En hausse';
                        this.tendanceMarche.className = 'tendance-hausse';
                    } else if (tendance === 'baisse') {
                        this.tendanceMarche.textContent = '📉 En baisse';
                        this.tendanceMarche.className = 'tendance-baisse';
                    } else {
                        this.tendanceMarche.textContent = '📊 Stable';
                        this.tendanceMarche.className = 'tendance-stable';
                    }
                }
                
                // Temps de vente estimé - utiliser la réponse de l'IA
                if (this.tempsVente) {
                    this.tempsVente.textContent = resultat.tempsVente || '2-4 semaines';
                }
                
                // Cacher la carte d'attente et afficher le résultat
                if (this.carteAttente) this.carteAttente.hidden = true;
                if (this.carteErreur) this.carteErreur.hidden = true;
                this.carteResultat.hidden = false;
                
                // Scroll vers le résultat sur mobile
                if (window.innerWidth < 1024) {
                    this.carteResultat.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                // Estimation impossible - afficher la carte d'erreur
                this.afficherErreurEstimation(donnees);
            }

        } catch (erreur) {
            console.error(erreur);
            this.afficherMessage(erreur.message, 'err');
        } finally {
            this.definirChargement(false);
        }
    }

    reinitialiserFormulaire() {
        this.carteResultat.hidden = true;
        if (this.carteErreur) this.carteErreur.hidden = true;
        if (this.carteAttente) this.carteAttente.hidden = false;
        this.formulaireEstimation.reset();
        this.afficherMessage('');
    }
    
    afficherErreurEstimation(donnees) {
        // Afficher le véhicule recherché
        if (this.erreurVehicule) {
            this.erreurVehicule.textContent = `${donnees.marque} ${donnees.modele} • ${donnees.annee}`;
        }
        
        // Cacher les autres cartes et afficher l'erreur
        if (this.carteAttente) this.carteAttente.hidden = true;
        this.carteResultat.hidden = true;
        if (this.carteErreur) this.carteErreur.hidden = false;
        
        // Scroll vers l'erreur sur mobile
        if (window.innerWidth < 1024 && this.carteErreur) {
            this.carteErreur.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}
