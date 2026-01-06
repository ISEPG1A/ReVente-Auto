/**
 * VueScoreIA - Affiche le curseur de score IA avec dégradé
 * Rouge (0) → Orange → Jaune → Vert → Bleu (100)
 */

export default class VueScoreIA {
    constructor(containerId = 'score-ia-container') {
        this.container = document.getElementById(containerId);
        this.apiBase = document.querySelector('meta[name="api-base"]')?.content || '';
    }

    /**
     * Initialiser et charger le score
     */
    async init(vehiculeId) {
        if (!this.container) {
            console.warn('Container score-ia non trouvé');
            return;
        }

        this.vehiculeId = vehiculeId;
        this.afficherChargement();
        
        try {
            const score = await this.chargerScore(vehiculeId);
            this.afficherScore(score);
        } catch (error) {
            console.error('Erreur chargement score IA:', error);
            this.afficherErreur();
        }
    }

    /**
     * Charger le score depuis l'API
     */
    async chargerScore(vehiculeId) {
        const response = await fetch(`${this.apiBase}/score-ia?vehicule_id=${vehiculeId}`);
        const data = await response.json();
        
        if (data.success) {
            return data.data;
        }
        throw new Error(data.message || 'Erreur');
    }

    /**
     * Afficher l'état de chargement
     */
    afficherChargement() {
        this.container.innerHTML = `
            <div class="score-ia__header">
                <div class="score-ia__title">
                    <i class="fas fa-robot"></i>
                    <span>Analyse IA de l'annonce</span>
                </div>
            </div>
            <div class="score-ia__loading">
                <div class="score-ia__spinner"></div>
                <span>Analyse en cours...</span>
            </div>
        `;
    }

    /**
     * Afficher le score avec le curseur
     */
    afficherScore(data) {
        const { score, label, conseil, alerte, raisonnement } = data;
        
        // Si score est null = véhicule non évalué ou impossible à évaluer
        if (score === null) {
            // Déterminer l'icône selon le type d'erreur
            const estInconnu = label === 'Impossible à évaluer';
            const icone = estInconnu ? 'fa-question-circle' : 'fa-info-circle';
            const couleurIcone = estInconnu ? '#e74c3c' : '#3498db';
            
            this.container.innerHTML = `
                <div class="score-ia__header">
                    <div class="score-ia__title">
                        <i class="fas fa-robot"></i>
                        <span>Analyse IA de l'annonce</span>
                    </div>
                </div>
                <div class="score-ia__unavailable" style="border-left: 4px solid ${couleurIcone};">
                    <i class="fas ${icone}" style="color: ${couleurIcone};"></i>
                    <div>
                        <strong>${label}</strong>
                        <p>${conseil}</p>
                        ${raisonnement ? `<small style="opacity: 0.7; display: block; margin-top: 5px;"><i class="fas fa-lightbulb"></i> ${raisonnement}</small>` : ''}
                    </div>
                </div>
                <div class="score-ia__disclaimer">
                    <i class="fas fa-exclamation-triangle"></i>
                    ${estInconnu 
                        ? 'Vérifiez que la marque et le modèle sont correctement orthographiés.' 
                        : 'Cette analyse IA est indicative. Vérifiez toujours l\'état réel du véhicule.'}
                </div>
            `;
            return;
        }
        
        // Calculer la couleur du curseur
        const couleur = this.getCouleur(score);
        
        // Construire le HTML d'alerte si présent
        const alerteHtml = alerte ? `
            <div class="score-ia__alerte">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${alerte}</p>
            </div>
        ` : '';
        
        this.container.innerHTML = `
            <div class="score-ia__header">
                <div class="score-ia__title">
                    <i class="fas fa-robot"></i>
                    <span>Analyse IA de l'annonce</span>
                </div>
                <div class="score-ia__badge" style="background: ${couleur}">
                    ${score}/100
                </div>
            </div>
            
            <div class="score-ia__gauge">
                <div class="score-ia__bar">
                    <div class="score-ia__cursor" style="left: ${score}%; background: ${couleur}">
                        <div class="score-ia__cursor-inner"></div>
                    </div>
                </div>
                <div class="score-ia__labels">
                    <span class="score-ia__label score-ia__label--bad">Prix élevé</span>
                    <span class="score-ia__label score-ia__label--good">Excellente affaire</span>
                </div>
            </div>
            
            <div class="score-ia__result">
                <div class="score-ia__verdict" style="color: ${couleur}">
                    <i class="${this.getIcone(score)}"></i>
                    <span>${label}</span>
                </div>
                <p class="score-ia__conseil">${conseil}</p>
            </div>
            
            ${alerteHtml}
            
            <div class="score-ia__disclaimer">
                <i class="fas fa-exclamation-triangle"></i>
                Cette analyse IA est indicative. Vérifiez toujours l'état réel du véhicule.
            </div>
        `;

        // Animation d'entrée du curseur
        setTimeout(() => {
            const cursor = this.container.querySelector('.score-ia__cursor');
            if (cursor) {
                cursor.classList.add('score-ia__cursor--animated');
            }
        }, 100);
    }

    /**
     * Afficher une erreur
     */
    afficherErreur() {
        this.container.innerHTML = `
            <div class="score-ia__header">
                <div class="score-ia__title">
                    <i class="fas fa-robot"></i>
                    <span>Analyse IA de l'annonce</span>
                </div>
            </div>
            <div class="score-ia__error">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Analyse non disponible</span>
            </div>
        `;
    }

    /**
     * Obtenir la couleur selon le score
     * 0-20: Rouge, 21-40: Orange, 41-60: Jaune, 61-80: Vert, 81-100: Bleu
     */
    getCouleur(score) {
        if (score <= 20) return '#ef4444'; // Rouge
        if (score <= 40) return '#f97316'; // Orange
        if (score <= 60) return '#eab308'; // Jaune
        if (score <= 80) return '#22c55e'; // Vert
        return '#3b82f6'; // Bleu
    }

    /**
     * Obtenir l'icône selon le score
     */
    getIcone(score) {
        if (score <= 20) return 'fas fa-times-circle';
        if (score <= 40) return 'fas fa-exclamation-circle';
        if (score <= 60) return 'fas fa-minus-circle';
        if (score <= 80) return 'fas fa-check-circle';
        return 'fas fa-star';
    }
}
