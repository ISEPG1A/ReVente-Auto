/**
 * ═══════════════════════════════════════════════════════════════════════════
 * VUE PROFIL - GESTION DU COMPTE UTILISATEUR
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Cette classe gère l'interface du profil utilisateur :
 * - Chargement et affichage des informations utilisateur
 * - Mise à jour du profil (nom, prénom, téléphone)
 * - Vérification de l'email (envoi du lien)
 * - Vérification du téléphone (code SMS)
 * - Suppression du compte
 * 
 * États de la page :
 * - Chargement : affiche un indicateur de chargement
 * - Connexion requise : invite l'utilisateur à se connecter
 * - Contenu : affiche le formulaire de profil
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @see     ControleurProfil (PHP) Pour le traitement serveur
 * ═══════════════════════════════════════════════════════════════════════════
 */

import { obtenirUrlApi } from '../../application.js';

export default class VueProfil {
    
    /**
     * Initialise la vue profil et charge les données utilisateur
     */
    constructor() {
        /** @type {string} URL de l'endpoint du profil */
        this.urlApi = obtenirUrlApi('/profil');
        
        // Démarrage automatique de l'initialisation
        this.initialiser();
    }

    /**
     * Initialise les références DOM et charge les données
     * 
     * Configure les éléments d'interface et attache les événements,
     * puis charge les données de l'utilisateur connecté.
     * 
     * @async
     */
    async initialiser() {
        // ═══════════════════════════════════════════════════════════════════
        // RÉFÉRENCES AUX ÉLÉMENTS DOM
        // ═══════════════════════════════════════════════════════════════════
        
        /** @type {HTMLElement|null} Indicateur de chargement */
        this.divChargement = document.getElementById('chargement-profil');
        
        /** @type {HTMLElement|null} Message de connexion requise */
        this.divConnexionRequise = document.getElementById('profil-connexion-requise');
        
        /** @type {HTMLElement|null} Conteneur du contenu du profil */
        this.divContenu = document.getElementById('contenu-profil');
        
        /** @type {HTMLFormElement|null} Formulaire des paramètres */
        this.formulaireParametres = document.getElementById('formulaire-parametres');
        
        /** @type {HTMLButtonElement|null} Bouton de suppression du compte */
        this.boutonSupprimer = document.getElementById('supprimer-compte');
        
        /** @type {HTMLButtonElement|null} Bouton de vérification email */
        this.boutonVerifierEmail = document.getElementById('bouton-verifier-email');
        
        /** @type {HTMLButtonElement|null} Bouton de changement de mot de passe */
        this.boutonChangerPassword = document.getElementById('bouton-changer-password');
        
        /** @type {HTMLButtonElement|null} Bouton d'envoi du code téléphone */
        this.boutonCodeTelephone = document.getElementById('bouton-code-telephone');
        
        /** @type {HTMLButtonElement|null} Bouton de vérification téléphone */
        this.boutonVerifierTelephone = document.getElementById('bouton-verifier-telephone');

        // Configuration des événements et chargement des données
        this.attacherEvenements();
        await this.chargerDonneesUtilisateur();
    }

    /**
     * Attache les écouteurs d'événements aux éléments interactifs
     * 
     * Configure les handlers pour tous les boutons et formulaires
     * de la page de profil.
     */
    attacherEvenements() {
        // Soumission du formulaire de mise à jour
        if (this.formulaireParametres) {
            this.formulaireParametres.addEventListener('submit', 
                (evenement) => this.gererMiseAJourProfil(evenement)
            );
        }
        
        // Suppression du compte
        if (this.boutonSupprimer) {
            this.boutonSupprimer.addEventListener('click', 
                () => this.gererSuppressionCompte()
            );
        }
        
        // Vérification de l'email
        if (this.boutonVerifierEmail) {
            this.boutonVerifierEmail.addEventListener('click', 
                () => this.gererVerificationEmail()
            );
        }
        
        // Changement de mot de passe
        if (this.boutonChangerPassword) {
            this.boutonChangerPassword.addEventListener('click', 
                () => this.gererChangementMotDePasse()
            );
        }
        
        // Demande du code téléphone
        if (this.boutonCodeTelephone) {
            this.boutonCodeTelephone.addEventListener('click', 
                () => this.gererDemandeCodeTelephone()
            );
        }
        
        // Vérification du code téléphone
        if (this.boutonVerifierTelephone) {
            this.boutonVerifierTelephone.addEventListener('click', 
                () => this.gererVerificationTelephone()
            );
        }
        
        // Navigation entre les sections
        this.configurerNavigation();
        
        // Preview de l'avatar
        this.configurerPreviewAvatar();
    }
    
    /**
     * Configure la navigation entre les sections de paramètres
     */
    configurerNavigation() {
        const navItems = document.querySelectorAll('.parametres-nav__item');
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                navItems.forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                
                const sectionId = item.getAttribute('href');
                document.querySelector(sectionId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }
    
    /**
     * Configure la prévisualisation de l'avatar avant upload
     */
    configurerPreviewAvatar() {
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatar-preview');
        if (avatarInput && avatarPreview) {
            avatarInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Avatar">`;
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        }
    }

    /**
     * Charge les données de l'utilisateur connecté depuis l'API
     * 
     * Affiche l'état approprié selon la réponse :
     * - Données utilisateur si connecté
     * - Message de connexion requise sinon
     * 
     * @async
     */
    async chargerDonneesUtilisateur() {
        try {
            // Affichage de l'état de chargement
            if (this.divChargement) this.divChargement.hidden = false;
            if (this.divConnexionRequise) this.divConnexionRequise.hidden = true;
            if (this.divContenu) this.divContenu.hidden = true;

            // Requête pour obtenir les données utilisateur
            const reponse = await fetch(this.urlApi + '?action=me', {
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            // Masquage du chargement
            if (this.divChargement) this.divChargement.hidden = true;

            if (reponse.ok && donnees.user) {
                // ═══════════════════════════════════════════════════════════
                // UTILISATEUR CONNECTÉ - AFFICHAGE DES DONNÉES
                // ═══════════════════════════════════════════════════════════
                
                if (this.divContenu) this.divContenu.hidden = false;

                // Remplissage des champs du formulaire
                const saisiePrenom = document.getElementById('prenom');
                const saisieNom = document.getElementById('nom');
                const saisieTelephone = document.getElementById('telephone');

                if (saisiePrenom) saisiePrenom.value = donnees.user.first_name || '';
                if (saisieNom) saisieNom.value = donnees.user.last_name || '';
                if (saisieTelephone) saisieTelephone.value = donnees.user.phone || '';

                // Affichage des statuts de vérification
                const statutEmail = document.getElementById('statut-email');
                const statutTelephone = document.getElementById('statut-telephone');
                
                if (statutEmail) {
                    const estVerifie = donnees.user.email_verified_at ? 'vérifié' : 'non vérifié';
                    statutEmail.innerHTML = `<i class="fas fa-circle"></i> Statut: ${estVerifie}`;
                    
                    // Masquer le bouton de vérification si l'email est déjà vérifié
                    if (donnees.user.email_verified_at && this.boutonVerifierEmail) {
                        this.boutonVerifierEmail.disabled = true;
                        this.boutonVerifierEmail.innerHTML = '<i class="fas fa-check-circle"></i> Déjà vérifié';
                        this.boutonVerifierEmail.style.opacity = '0.5';
                        this.boutonVerifierEmail.style.cursor = 'not-allowed';
                    }
                }
                
                if (statutTelephone) {
                    const estVerifie = donnees.user.phone_verified_at ? 'vérifié' : 'non vérifié';
                    statutTelephone.innerHTML = `<i class="fas fa-circle"></i> Statut: ${estVerifie}`;
                }
            } else {
                // ═══════════════════════════════════════════════════════════
                // UTILISATEUR NON CONNECTÉ
                // ═══════════════════════════════════════════════════════════
                
                if (this.divConnexionRequise) this.divConnexionRequise.hidden = false;
            }
            
        } catch (erreur) {
            console.error('Erreur lors du chargement du profil:', erreur);
            
            // Affichage d'un message d'erreur
            if (this.divChargement) this.divChargement.hidden = true;
            if (this.divContenu) {
                this.divContenu.hidden = false;
                this.divContenu.innerHTML = '<p class="message message--erreur">Erreur de chargement du profil.</p>';
            }
        }
    }

    /**
     * Traite la mise à jour des informations du profil
     * 
     * Envoie les nouvelles données au serveur et recharge
     * la page en cas de succès.
     * 
     * @param   {Event} evenement - Événement de soumission du formulaire
     * @async
     */
    async gererMiseAJourProfil(evenement) {
        evenement.preventDefault();
        
        // Conteneur pour les messages
        const conteneurMessages = this.formulaireParametres.querySelector('.messages-formulaire');
        conteneurMessages.innerHTML = '';
        
        // Préparation des données du formulaire
        const donneesFormulaire = new FormData(this.formulaireParametres);

        try {
            const reponse = await fetch(this.urlApi + '?action=update_profile', {
                method: 'POST',
                body: donneesFormulaire
            });
            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Erreur lors de la mise à jour');
            }

            // Message de succès et rechargement
            conteneurMessages.innerHTML = '<div class="message message--succes">Profil mis à jour avec succès.</div>';
            setTimeout(() => window.location.reload(), 600);
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    /**
     * Traite la demande de suppression du compte
     * 
     * Demande confirmation puis supprime le compte de manière
     * définitive et redirige vers l'accueil.
     * 
     * @async
     */
    async gererSuppressionCompte() {
        // Demande de confirmation avant suppression définitive
        const confirmation = confirm('Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.');
        if (!confirmation) return;

        try {
            const reponse = await fetch(this.urlApi + '?action=delete_account', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Suppression du compte impossible');
            }
            
            // Redirection vers l'accueil après suppression
            const urlBase = document.querySelector('base')?.href || '/';
            window.location.href = urlBase + 'accueil';
            
        } catch (erreur) {
            alert(erreur.message || 'Une erreur est survenue');
        }
    }

    /**
     * Demande l'envoi d'un lien de vérification d'email
     * 
     * Le serveur génère un lien unique qui sera envoyé à l'adresse
     * email de l'utilisateur (ou affiché en mode démo).
     * Avec système de cooldown de 30 secondes.
     * 
     * @async
     */
    async gererVerificationEmail() {
        const conteneurMessages = document.getElementById('message-verification-email');
        conteneurMessages.innerHTML = '';
        
        // Désactiver le bouton temporairement
        if (this.boutonVerifierEmail) {
            this.boutonVerifierEmail.disabled = true;
        }

        try {
            const reponse = await fetch(this.urlApi + '?action=request_email_verification', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (!reponse.ok) {
                // Si erreur de cooldown, gérer l'affichage du temps restant
                if (donnees.cooldown) {
                    this.demarrerCooldown(donnees.cooldown);
                }
                throw new Error(donnees.error || 'Envoi du lien impossible');
            }

            // Affichage du lien (mode démo) ou confirmation
            if (donnees.verification_link) {
                conteneurMessages.innerHTML = `<div class="message message--succes">Lien de vérification: <a href="${donnees.verification_link}">${donnees.verification_link}</a></div>`;
            } else {
                conteneurMessages.innerHTML = '<div class="message message--succes">Email de vérification envoyé avec succès.</div>';
            }
            
            // Démarrer le cooldown de 30 secondes
            this.demarrerCooldown(30);
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
            // Réactiver le bouton en cas d'erreur (sauf si cooldown)
            if (this.boutonVerifierEmail && !erreur.message.includes('attendre')) {
                this.boutonVerifierEmail.disabled = false;
            }
        }
    }
    
    /**
     * Démarre un cooldown sur le bouton de vérification email
     * 
     * @param {number} secondes - Nombre de secondes du cooldown
     */
    demarrerCooldown(secondes) {
        if (!this.boutonVerifierEmail) return;
        
        let tempsRestant = secondes;
        const texteOriginal = this.boutonVerifierEmail.innerHTML;
        
        // Mettre à jour l'affichage chaque seconde
        const interval = setInterval(() => {
            this.boutonVerifierEmail.disabled = true;
            this.boutonVerifierEmail.innerHTML = `<i class="fas fa-clock"></i> Attendre ${tempsRestant}s`;
            tempsRestant--;
            
            if (tempsRestant < 0) {
                clearInterval(interval);
                this.boutonVerifierEmail.disabled = false;
                this.boutonVerifierEmail.innerHTML = texteOriginal;
            }
        }, 1000);
    }

    /**
     * Demande l'envoi d'un code de vérification par téléphone
     * 
     * Le serveur génère un code qui sera envoyé par SMS
     * (ou affiché en mode démo).
     * 
     * @async
     */
    async gererDemandeCodeTelephone() {
        const conteneurMessages = document.getElementById('message-verification-telephone');
        conteneurMessages.innerHTML = '';

        try {
            const reponse = await fetch(this.urlApi + '?action=request_phone_code', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Envoi du code impossible');
            }
            
            // Affichage du code en mode démo
            conteneurMessages.innerHTML = `<div class="message message--succes">Code de vérification (démo): ${donnees.code}</div>`;
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    /**
     * Vérifie le code de vérification téléphone saisi
     * 
     * Compare le code saisi avec celui généré par le serveur
     * et marque le téléphone comme vérifié si correct.
     * 
     * @async
     */
    async gererVerificationTelephone() {
        // Récupération du code saisi
        const champCode = document.getElementById('code-telephone');
        const code = (champCode?.value || '').trim();
        
        const conteneurMessages = document.getElementById('message-verification-telephone');
        conteneurMessages.innerHTML = '';

        // Validation du code saisi
        if (!code) {
            conteneurMessages.innerHTML = '<div class="message message--erreur">Veuillez entrer le code de vérification.</div>';
            return;
        }

        try {
            const reponse = await fetch(this.urlApi + '?action=verify_phone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code })
            });
            const donnees = await reponse.json();

            if (!reponse.ok) {
                throw new Error(donnees.error || 'Vérification du code impossible');
            }
            
            conteneurMessages.innerHTML = '<div class="message message--succes">Numéro de téléphone vérifié avec succès.</div>';
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }
    
    /**
     * Demande l'envoi d'un email de réinitialisation de mot de passe
     * 
     * Le serveur génère un token de reset qui sera envoyé à l'adresse
     * email de l'utilisateur avec un lien vers la page de changement.
     * Avec système de cooldown de 30 secondes.
     * 
     * @async
     */
    async gererChangementMotDePasse() {
        const conteneurMessages = document.getElementById('message-reset-password');
        conteneurMessages.innerHTML = '';
        
        // Désactiver le bouton temporairement
        if (this.boutonChangerPassword) {
            this.boutonChangerPassword.disabled = true;
        }

        try {
            const reponse = await fetch(this.urlApi + '?action=request_password_reset', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (!reponse.ok) {
                // Si erreur de cooldown, gérer l'affichage du temps restant
                if (donnees.cooldown) {
                    this.demarrerCooldownPassword(donnees.cooldown);
                }
                throw new Error(donnees.error || 'Envoi de l\'email impossible');
            }

            // Affichage de la confirmation
            conteneurMessages.innerHTML = '<div class="message message--succes">Email de réinitialisation envoyé avec succès. Vérifiez votre boîte de réception.</div>';
            
            // Démarrer le cooldown de 30 secondes
            this.demarrerCooldownPassword(30);
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
            // Réactiver le bouton en cas d'erreur (sauf si cooldown)
            if (this.boutonChangerPassword && !erreur.message.includes('attendre')) {
                this.boutonChangerPassword.disabled = false;
            }
        }
    }
    
    /**
     * Démarre un cooldown sur le bouton de changement de mot de passe
     * 
     * @param {number} secondes - Nombre de secondes du cooldown
     */
    demarrerCooldownPassword(secondes) {
        if (!this.boutonChangerPassword) return;
        
        let tempsRestant = secondes;
        const texteOriginal = this.boutonChangerPassword.innerHTML;
        
        // Mettre à jour l'affichage chaque seconde
        const interval = setInterval(() => {
            this.boutonChangerPassword.disabled = true;
            this.boutonChangerPassword.innerHTML = `<i class="fas fa-clock"></i> Attendre ${tempsRestant}s`;
            tempsRestant--;
            
            if (tempsRestant < 0) {
                clearInterval(interval);
                this.boutonChangerPassword.disabled = false;
                this.boutonChangerPassword.innerHTML = texteOriginal;
            }
        }, 1000);
    }
}
