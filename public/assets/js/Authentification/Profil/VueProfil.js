import { obtenirUrlApi } from '../../app.js';

export default class VueProfil {
    constructor() {
        this.apiUrl = obtenirUrlApi('/profil');
        
        this.initialiser();
    }

    async initialiser() {
        this.divChargement = document.getElementById('chargement-profil');
        this.divConnexionRequise = document.getElementById('profil-connexion-requise');
        this.divContenu = document.getElementById('contenu-profil');
        
        this.formulaireParametres = document.getElementById('formulaire-parametres');
        this.boutonSupprimer = document.getElementById('supprimer-compte');
        this.boutonVerifierEmail = document.getElementById('bouton-verifier-email');
        this.boutonCodeTelephone = document.getElementById('bouton-code-telephone');
        this.boutonVerifierTelephone = document.getElementById('bouton-verifier-telephone');

        this.attacherEvenements();
        await this.chargerDonneesUtilisateur();
    }

    attacherEvenements() {
        if (this.formulaireParametres) {
            this.formulaireParametres.addEventListener('submit', (e) => this.gererMiseAJourProfil(e));
        }
        if (this.boutonSupprimer) {
            this.boutonSupprimer.addEventListener('click', () => this.gererSuppressionCompte());
        }
        if (this.boutonVerifierEmail) {
            this.boutonVerifierEmail.addEventListener('click', () => this.gererVerificationEmail());
        }
        if (this.boutonCodeTelephone) {
            this.boutonCodeTelephone.addEventListener('click', () => this.gererCodeTelephone());
        }
        if (this.boutonVerifierTelephone) {
            this.boutonVerifierTelephone.addEventListener('click', () => this.gererVerificationTelephone());
        }
    }

    async chargerDonneesUtilisateur() {
        try {
            if (this.divChargement) this.divChargement.hidden = false;
            if (this.divConnexionRequise) this.divConnexionRequise.hidden = true;
            if (this.divContenu) this.divContenu.hidden = true;

            const reponse = await fetch(this.apiUrl + '?action=me', {
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (this.divChargement) this.divChargement.hidden = true;

            if (reponse.ok && donnees.user) {
                // Utilisateur connecté
                if (this.divContenu) this.divContenu.hidden = false;

                const saisiePrenom = document.getElementById('prenom');
                const saisieNom = document.getElementById('nom');
                const saisieTelephone = document.getElementById('telephone');

                if (saisiePrenom) saisiePrenom.value = donnees.user.first_name || '';
                if (saisieNom) saisieNom.value = donnees.user.last_name || '';
                if (saisieTelephone) saisieTelephone.value = donnees.user.phone || '';

                const statutEmail = document.getElementById('statut-email');
                const statutTelephone = document.getElementById('statut-telephone');
                
                if (statutEmail) {
                    statutEmail.textContent = 'Statut email: ' + (donnees.user.email_verified_at ? 'vérifié' : 'non vérifié');
                }
                if (statutTelephone) {
                    statutTelephone.textContent = 'Statut téléphone: ' + (donnees.user.phone_verified_at ? 'vérifié' : 'non vérifié');
                }
            } else {
                // Non connecté
                if (this.divConnexionRequise) this.divConnexionRequise.hidden = false;
            }
        } catch (erreur) {
            console.error('Erreur chargement profil', erreur);
            if (this.divChargement) this.divChargement.hidden = true;
            if (this.divContenu) {
                this.divContenu.hidden = false;
                this.divContenu.innerHTML = '<p>Erreur de chargement.</p>';
            }
        }
    }

    async gererMiseAJourProfil(e) {
        e.preventDefault();
        const boiteMessage = this.formulaireParametres.querySelector('.messages-formulaire');
        boiteMessage.innerHTML = '';
        const donneesFormulaire = new FormData(this.formulaireParametres);

        try {
            const reponse = await fetch(this.apiUrl + '?action=update_profile', {
                method: 'POST',
                body: donneesFormulaire
            });
            const donnees = await reponse.json();

            if (!reponse.ok) throw new Error(donnees.error || 'Erreur mise à jour');

            boiteMessage.innerHTML = '<div class="message message--succes">Profil mis à jour.</div>';
            setTimeout(() => window.location.reload(), 600);
        } catch (erreur) {
            boiteMessage.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    async gererSuppressionCompte() {
        if (!confirm('Supprimer votre compte ? Cette action est définitive.')) return;

        try {
            const reponse = await fetch(this.apiUrl + '?action=delete_account', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (!reponse.ok) throw new Error(donnees.error || 'Suppression impossible');
            
            // Redirection vers l'accueil
            const urlBase = document.querySelector('base')?.href || '/';
            window.location.href = urlBase + 'accueil';
        } catch (erreur) {
            alert(erreur.message || 'Erreur');
        }
    }

    async gererVerificationEmail() {
        const boiteMessage = document.getElementById('message-verification-email');
        boiteMessage.innerHTML = '';

        try {
            const reponse = await fetch(this.apiUrl + '?action=request_email_verification', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (!reponse.ok) throw new Error(donnees.error || 'Envoi impossible');

            if (donnees.verification_link) {
                boiteMessage.innerHTML = `<div class="message message--succes">Lien: <a href="${donnees.verification_link}">${donnees.verification_link}</a></div>`;
            } else {
                boiteMessage.innerHTML = '<div class="message message--succes">Lien généré.</div>';
            }
        } catch (erreur) {
            boiteMessage.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    async gererCodeTelephone() {
        const boiteMessage = document.getElementById('message-verification-telephone');
        boiteMessage.innerHTML = '';

        try {
            const reponse = await fetch(this.apiUrl + '?action=request_phone_code', {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const donnees = await reponse.json();

            if (!reponse.ok) throw new Error(donnees.error || 'Envoi impossible');
            boiteMessage.innerHTML = `<div class="message message--succes">Code (démo): ${donnees.code}</div>`;
        } catch (erreur) {
            boiteMessage.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }

    async gererVerificationTelephone() {
        const code = (document.getElementById('code-telephone')?.value || '').trim();
        const boiteMessage = document.getElementById('message-verification-telephone');
        boiteMessage.innerHTML = '';

        if (!code) {
            boiteMessage.innerHTML = '<div class="message message--erreur">Entrez un code.</div>';
            return;
        }

        try {
            const reponse = await fetch(this.apiUrl + '?action=verify_phone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code })
            });
            const donnees = await reponse.json();

            if (!reponse.ok) throw new Error(donnees.error || 'Vérification impossible');
            boiteMessage.innerHTML = '<div class="message message--succes">Téléphone vérifié.</div>';
        } catch (erreur) {
            boiteMessage.innerHTML = `<div class="message message--erreur">${erreur.message}</div>`;
        }
    }
}
