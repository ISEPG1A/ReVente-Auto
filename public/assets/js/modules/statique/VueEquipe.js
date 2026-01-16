// Configuration équipe
const cfg = (typeof window !== 'undefined' && window.EQUIPE_CONFIG) || { baseUrl: '/', userId: 0, isAdmin: false };

// Variables globales
let memberId = null;

function initialiserEquipe() {
    CarteFlip();
    UpdateProfil();
}

function CarteFlip() {
    document.querySelectorAll('.membre').forEach((carte) => {
        carte.addEventListener('click', () => {
            carte.classList.toggle('retournee');
        });
    });
}

function UpdateProfil() {
    if (!cfg.isAdmin) return;

    const modalOverlay = document.getElementById('modal-detail-overlay');
    const btnCloseModal = document.getElementById('btn-close-modal');
    const btnCancelModal = document.getElementById('btn-cancel-modal');
    const formDetail = document.getElementById('form-detail-membre');
    const statusMessage = document.getElementById('status-message');
    const btnsEdit = document.querySelectorAll('.btn-edit-membre');
    const avatarInput = document.querySelector('input[name="avatar"]');
    const avatarPreview = document.getElementById('avatar-preview');

    // Fonctions utilitaires
    const ouvrirFenetre = () => {
        modalOverlay.classList.add('active');
    };

    const fermerFenetre = () => {
        modalOverlay.classList.remove('active');
        formDetail.reset();
        memberId = null;
    };

    const afficherStatus = (message, isSuccess = false) => {
        statusMessage.textContent = message;
        statusMessage.classList.add('show');
        statusMessage.className = 'modal-detail__status show';
        statusMessage.classList.add(isSuccess ? 'modal-detail__status--success' : 'modal-detail__status--error');
        
        if (isSuccess) {
            setTimeout(() => fermerFenetre(), 1500);
        }
    };

    // Prévisualisation de l'image avatar
    if (avatarInput) {
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    avatarPreview.src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Charger les données d'un membre
    async function chargerMembre(id) {
        try {
            const res = await fetch(cfg.baseUrl + `api/profil?action=get_profile&user_id=${id}`, {
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!res.ok || data.erreur) throw new Error(data.erreur || 'Erreur chargement');
            return data;
        } catch (err) {
            afficherStatus(err.message || 'Erreur lors du chargement', false);
            return null;
        }
    }

    // Remplir le formulaire
    function remplirFormulaire(data) {
        document.querySelector('input[name="first_name"]').value = data.first_name || '';
        document.querySelector('input[name="last_name"]').value = data.last_name || '';
        document.querySelector('input[name="email"]').value = data.email || '';
        document.querySelector('input[name="phone"]').value = data.phone || '';
        document.querySelector('input[name="poste"]').value = data.poste || '';
        
        if (data.avatar_path) {
            document.getElementById('avatar-preview').src = data.avatar_path;
        }
        
        document.getElementById('member-name').textContent = (data.first_name || '') + ' ' + (data.last_name || '');
        document.getElementById('member-role').textContent = 'Poste: ' + (data.poste || 'Non renseigné');
    }

    // Boutons d'édition
    btnsEdit.forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            const id = parseInt(btn.dataset.memberId);
            if (!id) return;
            
            memberId = id;
            const data = await chargerMembre(id);
            
            if (data) {
                remplirFormulaire(data);
                ouvrirFenetre();
            }
        });
    });

    // Fermeture de la fenetre modale
    [btnCloseModal, btnCancelModal].forEach((btn) => {
        if (btn) btn.addEventListener('click', fermerFenetre);
    });

    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) fermerFenetre();
    });

    // Soumission du formulaire
    if (formDetail) {
        formDetail.addEventListener('submit', async (e) => {
            e.preventDefault();
            afficherStatus('Enregistrement en cours...');
            
            const fd = new FormData(formDetail);
            fd.append('user_id', memberId);
            
            try {
                const res = await fetch(cfg.baseUrl + 'api/profil?action=update_profile', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin',
                });
                
                const data = await res.json();
                if (!res.ok || data.erreur) throw new Error(data.erreur || 'Erreur enregistrement');
                
                afficherStatus('Profil mis à jour avec succès! ✓', true);
            } catch (err) {
                afficherStatus(err.message || 'Une erreur est survenue.', false);
            }
        });
    }
}

// Initialiser au chargement
document.addEventListener('DOMContentLoaded', initialiserEquipe);
