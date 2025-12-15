/**
 * ═══════════════════════════════════════════════════════════════════════════
 * UTILITAIRES VÉHICULE - FONCTIONS PARTAGÉES POUR LES FORMULAIRES
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Ce module centralise les fonctions utilitaires communes aux formulaires
 * d'ajout et de modification de véhicules pour éviter la duplication de code.
 * 
 * Fonctionnalités :
 * - Filtrage des options Crit'Air selon le type de véhicule
 * - Filtrage des normes Euro selon le type de véhicule
 * - Adaptation des options de taille de coffre
 * - Règles de validation synchronisées avec le backend PHP
 * - Validation des champs texte
 * - Animation des erreurs
 * 
 * @author  Équipe ReVente-Auto
 * @version 2.0
 * @module  utilitaires-vehicule
 * ═══════════════════════════════════════════════════════════════════════════
 */

// ═══════════════════════════════════════════════════════════════════════════
// FILTRAGE DES OPTIONS SELON LE TYPE DE VÉHICULE
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Filtre les options Crit'Air selon le type de véhicule
 * 
 * Les motos n'ont pas de Crit'Air 0, 4 ou 5 disponibles.
 * Les voitures et camions ont accès à toutes les vignettes.
 * 
 * @param {string} typeVehicule - Type de véhicule ('voiture', 'moto' ou 'camion')
 */
export function filtrerOptionsCritAir(typeVehicule) {
    const selectCritAir = document.getElementById('crit_air');
    if (!selectCritAir) return;

    const valeurActuelle = selectCritAir.value;

    if (typeVehicule === 'moto') {
        // Motos : Crit'Air 1, 2, 3 uniquement (pas 0, 4, 5)
        selectCritAir.innerHTML = `
            <option value="">-- Non renseigné --</option>
            <option value="1">🟣 Crit'Air 1</option>
            <option value="2">🟡 Crit'Air 2</option>
            <option value="3">🟠 Crit'Air 3</option>
        `;
    } else {
        // Voitures et Camions : Crit'Air 0 à 5
        selectCritAir.innerHTML = `
            <option value="">-- Non renseigné --</option>
            <option value="0">🟢 Crit'Air 0 (Électrique)</option>
            <option value="1">🟣 Crit'Air 1</option>
            <option value="2">🟡 Crit'Air 2</option>
            <option value="3">🟠 Crit'Air 3</option>
            <option value="4">🟤 Crit'Air 4</option>
            <option value="5">⚫ Crit'Air 5</option>
        `;
    }

    // Restauration de la valeur si toujours valide
    const optionsDisponibles = Array.from(selectCritAir.options).map(opt => opt.value);
    if (optionsDisponibles.includes(valeurActuelle)) {
        selectCritAir.value = valeurActuelle;
    }
}

/**
 * Filtre les options Norme Euro selon le type de véhicule
 * 
 * Les motos utilisent Euro 3, 4, 5 uniquement.
 * Les voitures et camions ont accès à Euro 1 jusqu'à Euro 6d.
 * 
 * @param {string} typeVehicule - Type de véhicule ('voiture', 'moto' ou 'camion')
 */
export function filtrerOptionsNormeEuro(typeVehicule) {
    const selectNormeEuro = document.getElementById('norme_euro');
    if (!selectNormeEuro) return;

    const valeurActuelle = selectNormeEuro.value;

    if (typeVehicule === 'moto') {
        // Motos : Euro 3, 4, 5 uniquement
        selectNormeEuro.innerHTML = `
            <option value="">-- Non renseigné --</option>
            <option value="Euro 3">Euro 3</option>
            <option value="Euro 4">Euro 4</option>
            <option value="Euro 5">Euro 5</option>
        `;
    } else {
        // Voitures et Camions : Euro 1 à 6d (pour anciens véhicules aussi)
        selectNormeEuro.innerHTML = `
            <option value="">-- Non renseigné --</option>
            <option value="Euro 1">Euro 1</option>
            <option value="Euro 2">Euro 2</option>
            <option value="Euro 3">Euro 3</option>
            <option value="Euro 4">Euro 4</option>
            <option value="Euro 5">Euro 5</option>
            <option value="Euro 6">Euro 6</option>
            <option value="Euro 6d">Euro 6d</option>
        `;
    }

    // Restauration de la valeur si toujours valide
    const optionsDisponibles = Array.from(selectNormeEuro.options).map(opt => opt.value);
    if (optionsDisponibles.includes(valeurActuelle)) {
        selectNormeEuro.value = valeurActuelle;
    }
}

/**
 * Adapte les options de taille de coffre selon le type de véhicule
 * 
 * Pour les camions, le volume est exprimé en m³.
 * Pour les voitures, le volume est exprimé en litres.
 * 
 * @param {string} typeVehicule - Type de véhicule ('voiture' ou 'camion')
 */
export function adapterOptionsTailleCoffre(typeVehicule) {
    const selectTailleCoffre = document.getElementById('taille_coffre');
    if (!selectTailleCoffre) return;

    const valeurActuelle = selectTailleCoffre.value;

    if (typeVehicule === 'camion') {
        // Camions : Volume en m³
        selectTailleCoffre.innerHTML = `
            <option value="">-- Non renseigné --</option>
            <option value="petit">🔹 Petit (< 10 m³)</option>
            <option value="moyen">🔸 Moyen (10-20 m³)</option>
            <option value="grand">🔶 Grand (> 20 m³)</option>
        `;
    } else {
        // Voitures : Volume en Litres
        selectTailleCoffre.innerHTML = `
            <option value="">-- Non renseigné --</option>
            <option value="petit">🔹 Petit (< 300L)</option>
            <option value="moyen">🔸 Moyen (300-500L)</option>
            <option value="grand">🔶 Grand (> 500L)</option>
        `;
    }

    // Restauration de la valeur
    selectTailleCoffre.value = valeurActuelle;
}

/**
 * Adapte le label de la taille de coffre selon le type de véhicule
 * 
 * @param {string} typeVehicule - Type de véhicule ('voiture' ou 'camion')
 */
export function adapterLabelTailleCoffre(typeVehicule) {
    const labelTailleCoffre = document.getElementById('label-taille-coffre');
    if (!labelTailleCoffre) return;

    if (typeVehicule === 'camion') {
        labelTailleCoffre.textContent = 'Volume de chargement';
    } else {
        labelTailleCoffre.textContent = 'Taille du coffre';
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// RÈGLES DE VALIDATION (SYNCHRONISÉES AVEC LE BACKEND PHP)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Retourne les règles de validation strictes pour les champs de véhicule
 * 
 * Ces règles sont identiques à celles du backend PHP pour garantir
 * une validation cohérente côté client et serveur.
 * 
 * @returns {Object} Objet contenant les règles de validation par champ
 *                   Chaque règle contient : min, max, msg
 */
export function obtenirReglesValidation() {
    // L'année maximum est l'année courante ou 2025 (pour les véhicules neufs)
    const anneeMax = Math.min(new Date().getFullYear(), 2025);
    
    return {
        'annee': { 
            min: 1900, 
            max: anneeMax, 
            msg: `L'année doit être comprise entre 1900 et ${anneeMax}.` 
        },
        'km': { 
            min: 10, 
            max: 9999999, 
            msg: 'Le kilométrage doit être compris entre 10 et 9 999 999 km.' 
        },
        'prix': { 
            min: 50, 
            max: 10000000, 
            msg: 'Le prix doit être compris entre 50 € et 10 000 000 €.' 
        },
        'puissance_cv': { 
            min: 1, 
            max: 2000, 
            msg: 'La puissance doit être comprise entre 1 et 2000 CV.' 
        },
        'consommation': { 
            min: 0.1, 
            max: 99.9, 
            msg: 'La consommation doit être comprise entre 0.1 et 99.9.' 
        },
        'consommation_secondaire': { 
            min: 0.1, 
            max: 99.9, 
            msg: 'La consommation secondaire doit être comprise entre 0.1 et 99.9.' 
        },
        'emission_co2': { 
            min: 0, 
            max: 999, 
            msg: 'L\'émission de CO2 doit être comprise entre 0 et 999 g/km.' 
        },
        'autonomie': { 
            min: 50, 
            max: 9999, 
            msg: 'L\'autonomie doit être comprise entre 50 et 9999 km.' 
        },
        'longueur': { 
            min: 1.5, 
            max: 20, 
            msg: 'La longueur doit être comprise entre 1.5m et 20m.' 
        },
        'largeur': { 
            min: 1, 
            max: 4, 
            msg: 'La largeur doit être comprise entre 1m et 4m.' 
        },
        'hauteur': { 
            min: 0.5, 
            max: 5, 
            msg: 'La hauteur doit être comprise entre 0.5m et 5m.' 
        },
        'nb_portes': { 
            min: 2, 
            max: 6, 
            msg: 'Le nombre de portes doit être compris entre 2 et 6.' 
        }
    };
}

/**
 * Valide un champ texte (ville, couleur, provenance)
 * @param {string} champId - L'id du champ
 * @param {string} valeur - La valeur à valider
 * @returns {Object|null} Objet {valide, message} ou null si pas de validation spécifique
 */
export function validerChampTexte(champId, valeur) {
    if (!['ville', 'couleur', 'provenance'].includes(champId)) return null;
    if (!valeur || valeur.trim() === '') return null;

    // 1. Interdire les chiffres
    if (/\d/.test(valeur)) {
        return {
            valide: false,
            message: `Le champ ${champId} ne doit pas contenir de chiffres.`
        };
    }

    // 2. Validation des symboles
    if (champId === 'couleur') {
        // Couleur : Lettres et espaces uniquement
        if (!/^[a-zA-ZÀ-ÿ\s]+$/.test(valeur)) {
            return {
                valide: false,
                message: `La couleur ne doit contenir que des lettres et des espaces (pas de symboles).`
            };
        }
    } else {
        // Ville et Provenance : Lettres, espaces, tirets, apostrophes
        if (!/^[a-zA-ZÀ-ÿ\s\'-]+$/.test(valeur)) {
            return {
                valide: false,
                message: `Le champ ${champId} contient des caractères interdits (seuls tirets et apostrophes sont autorisés).`
            };
        }
    }

    return { valide: true, message: null };
}

/**
 * Animation de secousse pour champs invalides
 * @param {HTMLElement} element - L'élément à animer
 */
export function animerErreur(element) {
    element.animate([
        { transform: 'translateX(0)' },
        { transform: 'translateX(-10px)' },
        { transform: 'translateX(10px)' },
        { transform: 'translateX(0)' }
    ], { duration: 300, iterations: 1 });
}
