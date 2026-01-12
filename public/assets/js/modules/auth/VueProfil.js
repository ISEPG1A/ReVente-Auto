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

import { obtenirUrlApi, afficherNotificationGlobale, afficherModaleConfirmation } from '../../application.js';

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
        
        /** @type {HTMLButtonElement|null} Bouton pour afficher le formulaire de changement d'email */
        this.boutonChangerEmail = document.getElementById('bouton-changer-email');
        
        /** @type {HTMLButtonElement|null} Bouton pour envoyer le changement d'email */
        this.boutonEnvoyerChangementEmail = document.getElementById('bouton-envoyer-changement-email');
        
        /** @type {HTMLButtonElement|null} Bouton pour annuler le changement d'email */
        this.boutonAnnulerChangementEmail = document.getElementById('bouton-annuler-changement-email');
        
        /** @type {HTMLElement|null} Conteneur du formulaire de changement d'email */
        this.formulaireChangementEmail = document.getElementById('formulaire-changement-email');
        
        /** @type {HTMLInputElement|null} Toggle pour masquer le numéro de téléphone */
        this.toggleHidePhone = document.getElementById('toggle-hide-phone');

        // Chargement des données d'abord, puis configuration des événements
        await this.chargerDonneesUtilisateur();
        this.attacherEvenements();
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
        
        // Afficher le formulaire de changement d'email
        if (this.boutonChangerEmail) {
            this.boutonChangerEmail.addEventListener('click', 
                () => this.afficherFormulaireChangementEmail()
            );
        }
        
        // Envoyer le changement d'email
        if (this.boutonEnvoyerChangementEmail) {
            this.boutonEnvoyerChangementEmail.addEventListener('click', 
                () => this.gererChangementEmail()
            );
        }
        
        // Annuler le changement d'email
        if (this.boutonAnnulerChangementEmail) {
            this.boutonAnnulerChangementEmail.addEventListener('click', 
                () => this.cacherFormulaireChangementEmail()
            );
        }
        
        // Toggle masquage du numéro de téléphone
        if (this.toggleHidePhone) {
            this.toggleHidePhone.addEventListener('change', 
                () => this.gererMasquageTelephone()
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

                // Affichage de la photo de profil actuelle
                const avatarPreview = document.getElementById('avatar-preview');
                if (avatarPreview && donnees.user.avatar_url) {
                    avatarPreview.innerHTML = `<img src="${donnees.user.avatar_url}" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
                } else if (avatarPreview) {
                    avatarPreview.innerHTML = '<i class="fas fa-user"></i>';
                }

                // Affichage des statuts de vérification
                const statutEmail = document.getElementById('statut-email');
                
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
        // Modale de confirmation avant suppression définitive
        afficherModaleConfirmation(
            'Supprimer votre compte',
            'Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible. Toutes vos données seront définitivement perdues.',
            async () => {
                // Désactiver le bouton pour éviter les doubles clics
                if (this.boutonSupprimer) {
                    this.boutonSupprimer.disabled = true;
                }

                try {
                    const reponse = await fetch(this.urlApi + '?action=delete_account', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' }
                    });
                    const donnees = await reponse.json();

                    if (!reponse.ok) {
                        throw new Error(donnees.erreur || donnees.error || 'Suppression du compte impossible');
                    }
                    
                    // Redirection vers l'accueil après suppression
                    afficherNotificationGlobale('Votre compte a été supprimé avec succès.', 'success');
                    const urlBase = document.querySelector('base')?.href || '/';
                    setTimeout(() => window.location.href = urlBase + 'accueil', 1500);
                    
                } catch (erreur) {
                    console.error('Erreur suppression compte:', erreur);
                    afficherNotificationGlobale(erreur.message || 'Une erreur est survenue', 'error');
                    // Réactiver le bouton en cas d'erreur
                    if (this.boutonSupprimer) {
                        this.boutonSupprimer.disabled = false;
                    }
                }
            },
            'Supprimer mon compte',
            'error'
        );
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
    
    // ═══════════════════════════════════════════════════════════════════════
    // CHANGEMENT D'EMAIL
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Affiche le formulaire de changement d'email
     */
    afficherFormulaireChangementEmail() {
        if (this.formulaireChangementEmail) {
            this.formulaireChangementEmail.style.display = 'block';
            const champEmail = document.getElementById('nouvel-email');
            if (champEmail) {
                champEmail.focus();
            }
        }
    }
    
    /**
     * Cache le formulaire de changement d'email et réinitialise les champs
     */
    cacherFormulaireChangementEmail() {
        if (this.formulaireChangementEmail) {
            this.formulaireChangementEmail.style.display = 'none';
            document.getElementById('nouvel-email').value = '';
            document.getElementById('confirmation-nouvel-email').value = '';
        }
        const conteneurMessages = document.getElementById('message-changement-email');
        if (conteneurMessages) {
            conteneurMessages.innerHTML = '';
        }
    }
    
    /**
     * Gère le changement d'adresse email
     * Envoie un email de confirmation à la nouvelle adresse
     * 
     * @async
     */
    async gererChangementEmail() {
        const conteneurMessages = document.getElementById('message-changement-email');
        conteneurMessages.innerHTML = '';
        
        const nouvelEmail = document.getElementById('nouvel-email').value.trim();
        const confirmationEmail = document.getElementById('confirmation-nouvel-email').value.trim();
        
        // Validation côté client
        if (!nouvelEmail || !confirmationEmail) {
            conteneurMessages.innerHTML = '<div class="message message--erreur">Veuillez remplir tous les champs.</div>';
            return;
        }
        
        if (nouvelEmail !== confirmationEmail) {
            conteneurMessages.innerHTML = '<div class="message message--erreur">Les deux adresses email ne correspondent pas.</div>';
            return;
        }
        
        // Validation format email
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regexEmail.test(nouvelEmail)) {
            conteneurMessages.innerHTML = '<div class="message message--erreur">Format d\'email invalide.</div>';
            return;
        }
        
        // Désactiver le bouton temporairement
        if (this.boutonEnvoyerChangementEmail) {
            this.boutonEnvoyerChangementEmail.disabled = true;
        }
        
        try {
            const reponse = await fetch(this.urlApi + '?action=request_email_change', {
                method: 'POST',
                headers: { 
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ new_email: nouvelEmail })
            });
            const donnees = await reponse.json();
            
            if (!reponse.ok) {
                // Si erreur de cooldown, gérer l'affichage du temps restant
                if (donnees.cooldown) {
                    this.demarrerCooldownChangementEmail(donnees.cooldown);
                }
                throw new Error(donnees.erreur || donnees.error || 'Impossible d\'envoyer l\'email de confirmation');
            }
            
            // Affichage de la confirmation
            conteneurMessages.innerHTML = `<div class="message message--succes">${donnees.message || 'Un email de confirmation a été envoyé à la nouvelle adresse.'}</div>`;
            
            // Réinitialiser les champs
            document.getElementById('nouvel-email').value = '';
            document.getElementById('confirmation-nouvel-email').value = '';
            
            // Cacher le formulaire après quelques secondes
            setTimeout(() => {
                this.cacherFormulaireChangementEmail();
            }, 3000);
            
            // Démarrer le cooldown de 60 secondes
            this.demarrerCooldownChangementEmail(60);
            
        } catch (erreur) {
            conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
            // Réactiver le bouton en cas d'erreur (sauf si cooldown)
            if (this.boutonEnvoyerChangementEmail && !erreur.message.includes('attendre')) {
                this.boutonEnvoyerChangementEmail.disabled = false;
            }
        }
    }
    
    /**
     * Démarre un cooldown sur le bouton de changement d'email
     * 
     * @param {number} secondes - Nombre de secondes du cooldown
     */
    demarrerCooldownChangementEmail(secondes) {
        if (!this.boutonEnvoyerChangementEmail) return;
        
        let tempsRestant = secondes;
        const texteOriginal = this.boutonEnvoyerChangementEmail.innerHTML;
        
        // Désactiver aussi le bouton d'affichage du formulaire
        if (this.boutonChangerEmail) {
            this.boutonChangerEmail.disabled = true;
        }
        
        // Mettre à jour l'affichage chaque seconde
        const interval = setInterval(() => {
            this.boutonEnvoyerChangementEmail.disabled = true;
            this.boutonEnvoyerChangementEmail.innerHTML = `<i class="fas fa-clock"></i> Attendre ${tempsRestant}s`;
            tempsRestant--;
            
            if (tempsRestant < 0) {
                clearInterval(interval);
                this.boutonEnvoyerChangementEmail.disabled = false;
                this.boutonEnvoyerChangementEmail.innerHTML = texteOriginal;
                if (this.boutonChangerEmail) {
                    this.boutonChangerEmail.disabled = false;
                }
            }
        }, 1000);
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // MASQUAGE DU NUMÉRO DE TÉLÉPHONE
    // ═══════════════════════════════════════════════════════════════════════
    
    /**
     * Gère le changement du toggle de masquage du numéro de téléphone
     * 
     * @async
     */
    async gererMasquageTelephone() {
        const conteneurMessages = document.getElementById('message-hide-phone');
        if (conteneurMessages) {
            conteneurMessages.innerHTML = '';
        }
        
        if (!this.toggleHidePhone) return;
        
        const masquer = this.toggleHidePhone.checked;
        
        // Désactiver le toggle pendant la requête
        this.toggleHidePhone.disabled = true;
        
        try {
            const reponse = await fetch(this.urlApi + '?action=toggle_hide_phone', {
                method: 'POST',
                headers: { 
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ hide_phone: masquer ? 1 : 0 })
            });
            const donnees = await reponse.json();
            
            if (!reponse.ok) {
                throw new Error(donnees.error || 'Impossible de modifier le paramètre');
            }
            
            // Mise à jour de la session
            if (window.sessionUser) {
                window.sessionUser.hide_phone = masquer ? 1 : 0;
            }
            
            // Message de confirmation
            if (conteneurMessages) {
                const message = masquer 
                    ? 'Votre numéro de téléphone sera masqué sur vos annonces' 
                    : 'Votre numéro de téléphone sera visible sur vos annonces';
                conteneurMessages.innerHTML = `<div class="message message--succes">${message}</div>`;
                
                // Effacer le message après 3 secondes
                setTimeout(() => {
                    conteneurMessages.innerHTML = '';
                }, 3000);
            }
            
        } catch (erreur) {
            // En cas d'erreur, remettre le toggle à son état précédent
            this.toggleHidePhone.checked = !masquer;
            
            if (conteneurMessages) {
                conteneurMessages.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
            }
        } finally {
            // Réactiver le toggle
            this.toggleHidePhone.disabled = false;
        }
    }
}
