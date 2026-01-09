/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE ESTIMATION - ESTIMATION DU PRIX D'UN VÉHICULE
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère l'outil d'estimation de prix de véhicule :
 * - Formulaire de saisie des caractéristiques du véhicule
 * - Appel à l'API d'estimation (potentiellement IA)
 * - Affichage du résultat avec fourchette de prix
 * - Indicateurs de tendance du marché
 * - Estimation du temps de vente
 * 
 * Résultats affichés :
 * - Prix estimé (valeur centrale)
 * - Fourchette de prix (±15%)
 * - Tendance du marché (hausse/baisse/stable)
 * - Temps de vente estimé
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurEstimation (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';

export default class VueEstimation {
    
    /**
     * Initialise la vue estimation
     */
    constructor() {
        /** @type {string} URL de l'endpoint d'estimation */
        this.urlApi = obtenirUrlApi('/estimation');

        this.initialiser();
    }

    /**
     * Initialise les références DOM et configure les événements
     * 
     * Récupère tous les éléments nécessaires à l'affichage
     * des résultats et à l'interaction utilisateur.
     */
    initialiser() {
        // ═══════════════════════════════════════════════════════════════════
        // ÉLÉMENTS DU FORMULAIRE
        // ═══════════════════════════════════════════════════════════════════
        
        /** @type {HTMLFormElement|null} Formulaire d'estimation */
        this.formulaireEstimation = document.getElementById('formulaire-estimation');
        
        /** @type {HTMLButtonElement|null} Bouton de soumission */
        this.boutonSoumettre = document.getElementById('bouton-estimer');
        
        /** @type {HTMLElement|null} Indicateur de chargement */
        this.chargeur = this.boutonSoumettre?.querySelector('.chargeur');
        
        /** @type {HTMLElement|null} Texte du bouton */
        this.texteBouton = this.boutonSoumettre?.querySelector('.texte-bouton');
        
        /** @type {HTMLElement|null} Zone des messages d'erreur */
        this.messages = document.querySelector('.messages-formulaire');
        
        // ═══════════════════════════════════════════════════════════════════
        // CARTES D'ÉTAT
        // ═══════════════════════════════════════════════════════════════════
        
        /** @type {HTMLElement|null} Carte de résultat */
        this.carteResultat = document.getElementById('resultat-estimation');
        
        /** @type {HTMLElement|null} Carte d'attente */
        this.carteAttente = document.getElementById('estimation-waiting');
        
        /** @type {HTMLElement|null} Carte d'erreur */
        this.carteErreur = document.getElementById('estimation-error');
        
        // ═══════════════════════════════════════════════════════════════════
        // ÉLÉMENTS DE RÉSULTAT
        // ═══════════════════════════════════════════════════════════════════
        
        /** @type {HTMLElement|null} Affichage du prix estimé */
        this.valeurPrix = document.getElementById('valeur-prix');
        
        /** @type {HTMLElement|null} Affichage du véhicule estimé */
        this.resultatVehicule = document.getElementById('resultat-vehicule');
        
        /** @type {HTMLElement|null} Prix minimum de la fourchette */
        this.prixMin = document.getElementById('prix-min');
        
        /** @type {HTMLElement|null} Prix maximum de la fourchette */
        this.prixMax = document.getElementById('prix-max');
        
        /** @type {HTMLElement|null} Indicateur de tendance du marché */
        this.tendanceMarche = document.getElementById('tendance-marche');
        
        /** @type {HTMLElement|null} Temps de vente estimé */
        this.tempsVente = document.getElementById('temps-vente');
        
        /** @type {HTMLElement|null} Message d'erreur véhicule */
        this.erreurVehicule = document.getElementById('erreur-vehicule');
        
        // ═══════════════════════════════════════════════════════════════════
        // BOUTONS D'ACTION
        // ═══════════════════════════════════════════════════════════════════
        
        /** @type {HTMLButtonElement|null} Bouton nouvelle estimation */
        this.boutonNouvelleEstimation = document.getElementById('bouton-nouvelle-estimation');
        
        /** @type {HTMLButtonElement|null} Bouton réessayer */
        this.boutonReessayer = document.getElementById('bouton-reessayer');

        // Configuration des événements
        if (this.formulaireEstimation) {
            this.formulaireEstimation.addEventListener('submit', (evenement) => this.gererSoumission(evenement));
        }

        if (this.boutonNouvelleEstimation) {
            this.boutonNouvelleEstimation.addEventListener('click', () => this.reinitialiserFormulaire());
        }
        
        if (this.boutonReessayer) {
            this.boutonReessayer.addEventListener('click', () => this.reinitialiserFormulaire());
        }
    }

    /**
     * Active ou désactive l'état de chargement du bouton
     * 
     * @param {boolean} estEnChargement - true pour activer le chargement
     */
    definirChargement(estEnChargement) {
        if (this.boutonSoumettre) {
            this.boutonSoumettre.disabled = estEnChargement;
            if (this.chargeur) this.chargeur.hidden = !estEnChargement;
            if (this.texteBouton) this.texteBouton.style.opacity = estEnChargement ? '0.7' : '1';
        }
    }

    /**
     * Affiche un message dans la zone de messages
     * 
     * @param {string} texte - Message à afficher (vide pour effacer)
     * @param {string} type - Type de message ('ok' ou 'err')
     */
    afficherMessage(texte, type = 'ok') {
        if (this.messages) {
            if (!texte) {
                this.messages.innerHTML = '';
                return;
            }
            this.messages.innerHTML = `<div class="msg msg--${type}">${texte}</div>`;
        }
    }

    /**
     * Formate un prix en notation française
     * 
     * @param   {number} prix - Prix à formater
     * @returns {string} Prix formaté (ex: "15 000")
     */
    formaterPrix(prix) {
        return new Intl.NumberFormat('fr-FR').format(prix);
    }

    /**
     * Gère la soumission du formulaire d'estimation
     * 
     * Envoie les données au serveur et affiche le résultat
     * avec la fourchette de prix et les indicateurs de marché.
     * 
     * @param {Event} evenement - Événement de soumission
     * @async
     */
    async gererSoumission(evenement) {
        evenement.preventDefault();
        
        // Récupération des données du formulaire
        const donneesFormulaire = new FormData(this.formulaireEstimation);
        const donnees = Object.fromEntries(donneesFormulaire.entries());
        
        // Validation des champs obligatoires
        if (!donnees.marque || !donnees.modele || !donnees.annee) {
            this.afficherMessage('Veuillez remplir tous les champs obligatoires.', 'err');
            return;
        }

        try {
            this.definirChargement(true);
            this.afficherMessage('');
            
            // Envoi de la requête d'estimation
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

            // Traitement du résultat
            if (resultat.prix !== null) {
                const prix = resultat.prix;
                const prixMinimum = Math.round(prix * 0.85);
                const prixMaximum = Math.round(prix * 1.15);
                
                // Affichage du véhicule estimé
                if (this.resultatVehicule) {
                    this.resultatVehicule.textContent = `${donnees.marque} ${donnees.modele} • ${donnees.annee}`;
                }
                
                // Affichage du prix principal
                this.valeurPrix.textContent = this.formaterPrix(prix);
                
                // Affichage de la fourchette de prix
                if (this.prixMin) this.prixMin.textContent = this.formaterPrix(prixMinimum) + ' €';
                if (this.prixMax) this.prixMax.textContent = this.formaterPrix(prixMaximum) + ' €';
                
                // Affichage de la tendance du marché
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
                
                // Affichage du temps de vente estimé
                if (this.tempsVente) {
                    this.tempsVente.textContent = resultat.tempsVente || '2-4 semaines';
                }
                
                // Basculement des cartes d'affichage
                if (this.carteAttente) this.carteAttente.hidden = true;
                if (this.carteErreur) this.carteErreur.hidden = true;
                this.carteResultat.hidden = false;
                
                // Scroll vers le résultat sur mobile
                if (window.innerWidth < 1024) {
                    this.carteResultat.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                // Estimation impossible - affichage de l'erreur
                this.afficherErreurEstimation(donnees);
            }

        } catch (erreur) {
            console.error('Erreur d\'estimation:', erreur);
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
