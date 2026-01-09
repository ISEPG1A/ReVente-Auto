/**
 * GESTIONNAIRE CODE POSTAL
 * Gère la saisie du code postal et charge automatiquement les villes
 * via l'API gouvernementale française.
 */

class GestionnaireCodePostal {
    constructor() {
        this.champCodePostal = document.getElementById('code_postal');
        this.champVille = document.getElementById('ville');
        
        // URL de l'API de localisation
        this.apiUrl = 'api/localisation';
        
        this.debounceTimer = null;
        this.villesCache = {};
        this.codePostalValide = false;  // Flag pour validation
        
        this.initialiser();
    }
    
    initialiser() {
        if (!this.champCodePostal || !this.champVille) {
            console.warn('Champs code postal ou ville non trouvés');
            return;
        }
        
        // Écouter la saisie du code postal
        this.champCodePostal.addEventListener('input', (e) => {
            this.gererSaisieCodePostal(e.target.value);
        });
        
        // Validation au blur
        this.champCodePostal.addEventListener('blur', (e) => {
            this.validerCodePostal(e.target.value);
        });
        
        // Empêcher les caractères non numériques
        this.champCodePostal.addEventListener('keypress', (e) => {
            if (!/\d/.test(e.key) && e.key !== 'Backspace' && e.key !== 'Delete' && e.key !== 'Tab' && e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') {
                e.preventDefault();
            }
        });
        
        // Validation lors de la soumission du formulaire
        const form = this.champCodePostal.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.validerAvantSoumission()) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    
                    // Afficher un message dans le select ville
                    this.champVille.setCustomValidity('Veuillez sélectionner une ville valide');
                    this.champVille.reportValidity();
                    
                    return false;
                }
            }, true);  // Capture phase pour être prioritaire
        }
    }
    
    gererSaisieCodePostal(codePostal) {
        // Nettoyer le timer précédent
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        
        // Réinitialiser le flag de validation
        this.codePostalValide = false;
        
        // Réinitialiser le message de validation
        this.champVille.setCustomValidity('');
        
        // Réinitialiser le champ ville si le code postal est incomplet
        if (codePostal.length !== 5) {
            this.champVille.disabled = true;
            this.champVille.innerHTML = '<option value="">-- Entrez d\'abord le code postal --</option>';
            return;
        }
        
        // Valider le format
        if (!/^\d{5}$/.test(codePostal)) {
            return;
        }
        
        // Debounce pour éviter trop de requêtes
        this.debounceTimer = setTimeout(() => {
            this.chargerVilles(codePostal);
        }, 500);
    }
    
    async chargerVilles(codePostal) {
        // Vérifier le cache
        if (this.villesCache[codePostal]) {
            this.remplirSelectVille(this.villesCache[codePostal], codePostal);
            return;
        }
        
        // Afficher un loader
        this.champVille.disabled = true;
        this.champVille.innerHTML = '<option value="">⏳ Chargement des villes...</option>';
        
        try {
            const response = await fetch(`${this.apiUrl}?action=villes&code_postal=${codePostal}`);
            
            if (!response.ok) {
                throw new Error('Erreur lors de la récupération des villes');
            }
            
            const data = await response.json();
            
            if (data.success && data.villes && data.villes.length > 0) {
                // Mettre en cache
                this.villesCache[codePostal] = data.villes;
                
                // Remplir le select
                this.remplirSelectVille(data.villes, codePostal);
                
                // Marquer comme valide
                this.codePostalValide = true;
                
                this.retirerErreur();
            } else {
                this.champVille.innerHTML = '<option value="">-- Code postal introuvable --</option>';
                this.champVille.disabled = false;  // Garder activé pour validation HTML5
                this.champVille.setCustomValidity('Ce code postal n\'existe pas');
                this.codePostalValide = false;
            }
            
        } catch (error) {
            console.error('Erreur:', error);
            this.champVille.innerHTML = '<option value="">-- Erreur de chargement --</option>';
            this.champVille.disabled = false;  // Garder activé pour validation HTML5
            this.champVille.setCustomValidity('Impossible de charger les villes. Vérifiez le code postal.');
            this.codePostalValide = false;
        }
    }
    
    remplirSelectVille(villes, codePostal) {
        this.champVille.innerHTML = '<option value="">-- Sélectionnez une ville --</option>';
        
        villes.forEach(ville => {
            const option = document.createElement('option');
            option.value = ville.nom;
            option.textContent = ville.nom;
            option.dataset.latitude = ville.latitude;
            option.dataset.longitude = ville.longitude;
            this.champVille.appendChild(option);
        });
        
        // Réinitialiser le message de validation personnalisé
        this.champVille.setCustomValidity('');
        
        // Activer le champ
        this.champVille.disabled = false;
        
        // Si une seule ville, la sélectionner automatiquement
        if (villes.length === 1) {
            this.champVille.value = villes[0].nom;
            this.champVille.dispatchEvent(new Event('change'));
        }
    }
    
    validerCodePostal(codePostal) {
        if (!codePostal) {
            return false;
        }
        
        if (!/^\d{5}$/.test(codePostal)) {
            return false;
        }
        
        return true;
    }
    
    validerAvantSoumission() {
        const codePostal = this.champCodePostal.value.trim();
        const ville = this.champVille.value;
        
        if (codePostal && (!this.codePostalValide || !ville)) {
            this.champVille.setCustomValidity('Veuillez sélectionner une ville valide pour ce code postal');
            this.champVille.reportValidity();
            this.champCodePostal.focus();
            return false;
        }
        
        if (!codePostal || !ville) {
            if (this.champCodePostal.hasAttribute('required') && !codePostal) {
                this.champCodePostal.reportValidity();
                return false;
            }
            if (this.champVille.hasAttribute('required') && !ville) {
                this.champVille.reportValidity();
                return false;
            }
        }
        
        return true;
    }
    
    afficherErreur(message) {
        this.retirerErreur();
        this.champCodePostal.classList.add('saisie--erreur');
        
        // Créer le message d'erreur
        const erreurDiv = document.createElement('small');
        erreurDiv.className = 'champ-erreur';
        erreurDiv.style.color = 'var(--danger)';
        erreurDiv.style.display = 'block';
        erreurDiv.style.marginTop = '0.5rem';
        erreurDiv.textContent = message;
        this.champCodePostal.parentNode.appendChild(erreurDiv);
    }
    
    retirerErreur() {
        this.champCodePostal.classList.remove('saisie--erreur');
        
        const erreur = this.champCodePostal.parentNode.querySelector('.champ-erreur');
        if (erreur) {
            erreur.remove();
        }
    }
    
    async preremplir(codePostal, ville) {
        if (codePostal && /^\d{5}$/.test(codePostal)) {
            this.champCodePostal.value = codePostal;
            
            try {
                await this.chargerVilles(codePostal);
                
                if (ville) {
                    setTimeout(() => {
                        const options = Array.from(this.champVille.options);
                        const optionTrouvee = options.find(opt => 
                            opt.value.toLowerCase() === ville.toLowerCase()
                        );
                        
                        if (optionTrouvee) {
                            this.champVille.value = optionTrouvee.value;
                        }
                    }, 300);
                }
            } catch (error) {
                console.error('Erreur lors du pré-remplissage:', error);
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.gestionnaireCodePostal = new GestionnaireCodePostal();
});
