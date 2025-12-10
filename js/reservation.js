/**
 * TAXI JULIEN - Gestion des Réservations
 * Formulaire de réservation avec envoi via EmailJS
 */

// ============================================
// CONFIGURATION EMAILJS
// ============================================

// À CONFIGURER : Remplacez ces valeurs par vos identifiants EmailJS
const EMAILJS_CONFIG = {
    serviceID: 'YOUR_SERVICE_ID',      // À remplacer
    templateID: 'YOUR_TEMPLATE_ID',    // À remplacer
    publicKey: 'YOUR_PUBLIC_KEY'       // À remplacer
};

// Initialiser EmailJS
(function() {
    if (typeof emailjs !== 'undefined') {
        emailjs.init(EMAILJS_CONFIG.publicKey);
    }
})();

// ============================================
// GESTION DU FORMULAIRE
// ============================================

document.addEventListener('DOMContentLoaded', function() {

    const form = document.getElementById('reservation-form');
    const submitBtn = document.getElementById('submit-btn');
    const confirmationMessage = document.getElementById('confirmation-message');
    const typeServiceSelect = document.getElementById('type-service');
    const infoConventionne = document.getElementById('info-conventionne');
    const dateCourse = document.getElementById('date-course');

    // Définir la date minimale à aujourd'hui
    if (dateCourse) {
        const today = new Date().toISOString().split('T')[0];
        dateCourse.setAttribute('min', today);
        dateCourse.value = today;
    }

    // Afficher l'info si service conventionné
    if (typeServiceSelect && infoConventionne) {
        typeServiceSelect.addEventListener('change', function() {
            if (this.value === 'conventionne') {
                infoConventionne.style.display = 'block';
            } else {
                infoConventionne.style.display = 'none';
            }
        });
    }

    // Soumission du formulaire
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validation du formulaire
            if (!window.TaxiJulien.validateForm(form)) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // Vérifier le consentement RGPD
            const rgpdConsent = document.getElementById('rgpd-consent');
            if (!rgpdConsent.checked) {
                alert('Veuillez accepter la politique de confidentialité pour continuer.');
                return;
            }

            // Désactiver le bouton et afficher un loader
            submitBtn.disabled = true;
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="spinner"></div> Envoi en cours...';

            try {
                // Préparer les données du formulaire
                const formData = {
                    nom: document.getElementById('nom').value,
                    prenom: document.getElementById('prenom').value,
                    telephone: document.getElementById('telephone').value,
                    email: document.getElementById('email').value,
                    type_service: document.getElementById('type-service').value,
                    adresse_depart: document.getElementById('adresse-depart').value,
                    adresse_arrivee: document.getElementById('adresse-arrivee').value,
                    date_course: formatDate(document.getElementById('date-course').value),
                    heure_course: document.getElementById('heure-course').value,
                    nb_passagers: document.getElementById('nb-passagers').value,
                    nb_bagages: document.getElementById('nb-bagages').value,
                    commentaire: document.getElementById('commentaire').value || 'Aucun',
                    date_reservation: new Date().toLocaleString('fr-FR')
                };

                // Envoyer via EmailJS
                if (typeof emailjs !== 'undefined') {
                    await sendEmailViaEmailJS(formData);
                } else {
                    // Mode démo sans EmailJS
                    console.log('Mode démo - Données du formulaire:', formData);
                    await new Promise(resolve => setTimeout(resolve, 1000)); // Simuler l'envoi
                }

                // Succès
                form.style.display = 'none';
                confirmationMessage.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });

                // Envoyer un événement Google Analytics (si configuré)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'reservation_submitted', {
                        'event_category': 'Formulaire',
                        'event_label': formData.type_service
                    });
                }

            } catch (error) {
                console.error('Erreur lors de l\'envoi:', error);

                alert('Une erreur est survenue lors de l\'envoi de votre réservation. Veuillez réessayer ou nous contacter directement par téléphone au 01 23 45 67 89.');

                // Réactiver le bouton
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
});

// ============================================
// ENVOI EMAILJS
// ============================================

async function sendEmailViaEmailJS(formData) {
    // Vérifier que EmailJS est configuré
    if (EMAILJS_CONFIG.serviceID === 'YOUR_SERVICE_ID') {
        console.warn('⚠️ EmailJS n\'est pas configuré. Voir le README pour les instructions.');
        throw new Error('EmailJS non configuré');
    }

    // Envoyer l'email
    return emailjs.send(
        EMAILJS_CONFIG.serviceID,
        EMAILJS_CONFIG.templateID,
        formData
    );
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

/**
 * Formate une date ISO en format français
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    return date.toLocaleDateString('fr-FR', options);
}

/**
 * Formate les données pour l'affichage
 */
function formatReservationData(data) {
    const typeServiceLabels = {
        'conventionne': 'Transport Conventionné CPAM',
        'classique': 'Trajet Classique',
        'aeroport': 'Aéroport / Gare',
        'longue-distance': 'Longue Distance',
        'mise-a-disposition': 'Mise à Disposition'
    };

    return {
        ...data,
        type_service: typeServiceLabels[data.type_service] || data.type_service
    };
}
