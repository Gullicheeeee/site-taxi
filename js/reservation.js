/**
 * TAXI JULIEN - Gestion des Réservations
 * Formulaire de réservation avec envoi via EmailJS
 *
 * FONCTIONNALITÉS:
 * - Sauvegarde automatique localStorage (évite perte de données)
 * - Tracking GTM pour analyse des conversions
 * - Validation robuste avec feedback visuel
 * - Gestion des étapes du formulaire
 */

// ============================================
// FLAG DEBUG
// ============================================
const DEBUG = false;

function log(...args) {
    if (DEBUG) console.log('[Reservation]', ...args);
}

// ============================================
// CONFIGURATION EMAILJS
// ============================================

const EMAILJS_CONFIG = {
    serviceID: 'YOUR_SERVICE_ID',
    templateID: 'YOUR_TEMPLATE_ID',
    publicKey: 'YOUR_PUBLIC_KEY'
};

// Initialiser EmailJS
(function() {
    if (typeof emailjs !== 'undefined') {
        emailjs.init(EMAILJS_CONFIG.publicKey);
    }
})();

// ============================================
// CONSTANTES
// ============================================

const STORAGE_KEY_RESERVATION = 'taxijulien_reservation_data';
const FORM_FIELDS = [
    'nom', 'prenom', 'telephone', 'email',
    'type-service', 'adresse-depart', 'adresse-arrivee',
    'date-course', 'heure-course', 'nb-passagers', 'nb-bagages',
    'commentaire'
];

// ============================================
// GESTION DU FORMULAIRE
// ============================================

let formStarted = false;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reservation-form');
    const submitBtn = document.getElementById('submit-btn');
    const confirmationMessage = document.getElementById('confirmation-message');
    const typeServiceSelect = document.getElementById('type-service');
    const infoConventionne = document.getElementById('info-conventionne');
    const dateCourse = document.getElementById('date-course');
    const heureCourse = document.getElementById('heure-course');
    const telephoneInput = document.getElementById('telephone');

    // Définir la date minimale à aujourd'hui
    if (dateCourse) {
        const today = new Date().toISOString().split('T')[0];
        dateCourse.setAttribute('min', today);
        dateCourse.value = today;
    }

    // Définir une heure par défaut (prochaine heure ronde)
    if (heureCourse && !heureCourse.value) {
        const now = new Date();
        const nextHour = new Date(now.getTime() + 60 * 60 * 1000);
        nextHour.setMinutes(0);
        const hours = String(nextHour.getHours()).padStart(2, '0');
        heureCourse.value = `${hours}:00`;
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

    // Configurer clavier numérique pour téléphone sur mobile
    if (telephoneInput) {
        telephoneInput.setAttribute('inputmode', 'tel');
        telephoneInput.setAttribute('autocomplete', 'tel');
    }

    // Restaurer les données sauvegardées
    restoreReservationData();

    // Configurer la sauvegarde automatique
    setupAutoSave();

    // Tracking: form_start au premier focus
    setupFormStartTracking();

    // Soumission du formulaire
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            await handleFormSubmit(form, submitBtn, confirmationMessage);
        });
    }

    // Initialiser Google Places Autocomplete pour les adresses
    initAddressAutocomplete();

    log('Formulaire de réservation initialisé');
});

// ============================================
// TRACKING GTM / DATALAYER
// ============================================

function trackEvent(eventName, eventData = {}) {
    try {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: eventName,
            ...eventData
        });
        log('Event tracked:', eventName, eventData);
    } catch (error) {
        log('Erreur tracking:', error);
    }
}

function setupFormStartTracking() {
    const form = document.getElementById('reservation-form');
    if (!form) return;

    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            if (!formStarted) {
                formStarted = true;
                trackEvent('form_start', {
                    form_name: 'reservation',
                    page: 'reservation'
                });
            }
        }, { once: true });
    });
}

// ============================================
// SAUVEGARDE LOCALSTORAGE
// ============================================

function saveReservationData() {
    try {
        const data = {
            timestamp: Date.now()
        };

        FORM_FIELDS.forEach(fieldId => {
            const input = document.getElementById(fieldId);
            if (input) {
                data[fieldId] = input.type === 'checkbox' ? input.checked : input.value;
            }
        });

        localStorage.setItem(STORAGE_KEY_RESERVATION, JSON.stringify(data));
        log('Données réservation sauvegardées');
    } catch (error) {
        log('Erreur sauvegarde localStorage:', error);
    }
}

function restoreReservationData() {
    try {
        const saved = localStorage.getItem(STORAGE_KEY_RESERVATION);
        if (!saved) return;

        const data = JSON.parse(saved);

        // Ne restaurer que si les données ont moins de 7 jours
        const maxAge = 7 * 24 * 60 * 60 * 1000; // 7 jours
        if (Date.now() - data.timestamp > maxAge) {
            localStorage.removeItem(STORAGE_KEY_RESERVATION);
            return;
        }

        // Restaurer les valeurs (sauf date/heure qui doivent être actuelles)
        const fieldsToRestore = ['nom', 'prenom', 'telephone', 'email', 'type-service',
            'adresse-depart', 'adresse-arrivee', 'nb-passagers', 'nb-bagages', 'commentaire'];

        fieldsToRestore.forEach(fieldId => {
            if (data[fieldId]) {
                const input = document.getElementById(fieldId);
                if (input) {
                    input.value = data[fieldId];
                }
            }
        });

        // Afficher l'info conventionné si nécessaire
        if (data['type-service'] === 'conventionne') {
            const infoConventionne = document.getElementById('info-conventionne');
            if (infoConventionne) infoConventionne.style.display = 'block';
        }

        log('Données réservation restaurées');

        // Afficher un indicateur que des données ont été restaurées
        showRestoreNotification();
    } catch (error) {
        log('Erreur restauration localStorage:', error);
    }
}

function showRestoreNotification() {
    const form = document.getElementById('reservation-form');
    if (!form) return;

    const hasData = ['nom', 'prenom', 'telephone', 'email'].some(id => {
        const input = document.getElementById(id);
        return input && input.value.trim();
    });

    if (!hasData) return;

    const notification = document.createElement('div');
    notification.className = 'restore-notification';
    notification.style.cssText = `
        background: #e8f4fd;
        border: 1px solid #bee5eb;
        color: #0c5460;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
    `;
    notification.innerHTML = `
        <span>Vos informations précédentes ont été restaurées.</span>
        <button type="button" onclick="clearSavedReservation()" style="
            background: none;
            border: none;
            color: #0c5460;
            text-decoration: underline;
            cursor: pointer;
            font-size: 0.85rem;
        ">Effacer</button>
    `;

    form.insertBefore(notification, form.firstChild);

    // Supprimer après 10 secondes
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s';
        setTimeout(() => notification.remove(), 300);
    }, 10000);
}

// Fonction globale pour effacer les données sauvegardées
window.clearSavedReservation = function() {
    localStorage.removeItem(STORAGE_KEY_RESERVATION);
    FORM_FIELDS.forEach(fieldId => {
        const input = document.getElementById(fieldId);
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = false;
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            } else {
                input.value = '';
            }
        }
    });

    const notification = document.querySelector('.restore-notification');
    if (notification) notification.remove();

    log('Données réservation effacées');
};

function setupAutoSave() {
    FORM_FIELDS.forEach(fieldId => {
        const input = document.getElementById(fieldId);
        if (input) {
            const eventType = input.type === 'checkbox' ? 'change' : 'input';
            input.addEventListener(eventType, debounce(saveReservationData, 500));
        }
    });
}

// ============================================
// GOOGLE PLACES AUTOCOMPLETE
// ============================================

function initAddressAutocomplete() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
        log('Google Places API non chargée');
        return;
    }

    const departInput = document.getElementById('adresse-depart');
    const arriveeInput = document.getElementById('adresse-arrivee');

    const options = {
        componentRestrictions: { country: 'fr' },
        fields: ['formatted_address', 'geometry', 'name'],
        types: ['geocode', 'establishment']
    };

    // Biais vers la région de Martigues
    const martiguesBounds = new google.maps.LatLngBounds(
        new google.maps.LatLng(43.25, 4.75),
        new google.maps.LatLng(43.55, 5.45)
    );

    try {
        if (departInput) {
            const autocompleteDepart = new google.maps.places.Autocomplete(departInput, options);
            autocompleteDepart.setBounds(martiguesBounds);
            autocompleteDepart.addListener('place_changed', function() {
                const place = autocompleteDepart.getPlace();
                if (place && place.formatted_address) {
                    departInput.value = place.formatted_address;
                    saveReservationData();
                }
            });
        }

        if (arriveeInput) {
            const autocompleteArrivee = new google.maps.places.Autocomplete(arriveeInput, options);
            autocompleteArrivee.setBounds(martiguesBounds);
            autocompleteArrivee.addListener('place_changed', function() {
                const place = autocompleteArrivee.getPlace();
                if (place && place.formatted_address) {
                    arriveeInput.value = place.formatted_address;
                    saveReservationData();
                }
            });
        }

        log('Google Places Autocomplete initialisé pour réservation');
    } catch (error) {
        console.error('Erreur initialisation Google Places:', error);
    }
}

// ============================================
// VALIDATION DU FORMULAIRE
// ============================================

function validateReservationForm(form) {
    let isValid = true;
    const errors = [];

    // Validation nom
    const nom = document.getElementById('nom');
    if (!nom.value.trim() || nom.value.trim().length < 2) {
        showFieldError(nom, 'Veuillez entrer votre nom');
        errors.push('nom');
        isValid = false;
    } else {
        removeFieldError(nom);
    }

    // Validation prénom
    const prenom = document.getElementById('prenom');
    if (!prenom.value.trim() || prenom.value.trim().length < 2) {
        showFieldError(prenom, 'Veuillez entrer votre prénom');
        errors.push('prenom');
        isValid = false;
    } else {
        removeFieldError(prenom);
    }

    // Validation téléphone
    const telephone = document.getElementById('telephone');
    if (!validatePhone(telephone.value)) {
        showFieldError(telephone, 'Numéro de téléphone invalide');
        errors.push('telephone');
        isValid = false;
    } else {
        removeFieldError(telephone);
    }

    // Validation email
    const email = document.getElementById('email');
    if (!validateEmail(email.value)) {
        showFieldError(email, 'Adresse email invalide');
        errors.push('email');
        isValid = false;
    } else {
        removeFieldError(email);
    }

    // Validation type de service
    const typeService = document.getElementById('type-service');
    if (!typeService.value) {
        showFieldError(typeService, 'Veuillez sélectionner un type de service');
        errors.push('type-service');
        isValid = false;
    } else {
        removeFieldError(typeService);
    }

    // Validation adresse départ
    const adresseDepart = document.getElementById('adresse-depart');
    if (!adresseDepart.value.trim() || adresseDepart.value.trim().length < 5) {
        showFieldError(adresseDepart, 'Veuillez entrer une adresse de départ complète');
        errors.push('adresse-depart');
        isValid = false;
    } else {
        removeFieldError(adresseDepart);
    }

    // Validation adresse arrivée
    const adresseArrivee = document.getElementById('adresse-arrivee');
    if (!adresseArrivee.value.trim() || adresseArrivee.value.trim().length < 5) {
        showFieldError(adresseArrivee, 'Veuillez entrer une adresse d\'arrivée complète');
        errors.push('adresse-arrivee');
        isValid = false;
    } else {
        removeFieldError(adresseArrivee);
    }

    // Validation date
    const dateCourse = document.getElementById('date-course');
    if (!dateCourse.value) {
        showFieldError(dateCourse, 'Veuillez sélectionner une date');
        errors.push('date-course');
        isValid = false;
    } else {
        const selectedDate = new Date(dateCourse.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
            showFieldError(dateCourse, 'La date ne peut pas être dans le passé');
            errors.push('date-course');
            isValid = false;
        } else {
            removeFieldError(dateCourse);
        }
    }

    // Validation heure
    const heureCourse = document.getElementById('heure-course');
    if (!heureCourse.value) {
        showFieldError(heureCourse, 'Veuillez sélectionner une heure');
        errors.push('heure-course');
        isValid = false;
    } else {
        removeFieldError(heureCourse);
    }

    // Vérifier le consentement RGPD
    const rgpdConsent = document.getElementById('rgpd-consent');
    if (!rgpdConsent.checked) {
        showFieldError(rgpdConsent.parentElement, 'Veuillez accepter la politique de confidentialité');
        errors.push('rgpd-consent');
        isValid = false;
    } else {
        removeFieldError(rgpdConsent.parentElement);
    }

    // Tracking des erreurs de validation
    if (!isValid) {
        trackEvent('form_validation_error', {
            form_name: 'reservation',
            error_fields: errors.join(',')
        });
    }

    return isValid;
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const cleaned = phone.replace(/[\s\.\-]/g, '');
    const re = /^(\+33|0)[1-9]\d{8}$/;
    return re.test(cleaned);
}

function showFieldError(input, message) {
    if (!input) return;

    input.classList.add('error');
    if (input.style) input.style.borderColor = '#dc3545';

    let errorDiv = input.parentElement.querySelector('.field-error-message');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error-message';
        errorDiv.style.cssText = 'color: #dc3545; font-size: 0.85rem; margin-top: 0.25rem;';
        input.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function removeFieldError(input) {
    if (!input) return;

    input.classList.remove('error');
    if (input.style) input.style.borderColor = '';

    const errorDiv = input.parentElement.querySelector('.field-error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// ============================================
// SOUMISSION DU FORMULAIRE
// ============================================

async function handleFormSubmit(form, submitBtn, confirmationMessage) {
    // Validation du formulaire
    if (!validateReservationForm(form)) {
        // Scroll vers la première erreur
        const firstError = form.querySelector('.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
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
        if (typeof emailjs !== 'undefined' && EMAILJS_CONFIG.serviceID !== 'YOUR_SERVICE_ID') {
            await sendEmailViaEmailJS(formData);
        } else {
            // Mode démo sans EmailJS
            log('Mode démo - Données du formulaire:', formData);
            await new Promise(resolve => setTimeout(resolve, 1000));
        }

        // Succès
        form.style.display = 'none';
        confirmationMessage.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Supprimer les données sauvegardées après envoi réussi
        localStorage.removeItem(STORAGE_KEY_RESERVATION);

        // Tracking: form_submit
        trackEvent('form_submit', {
            form_name: 'reservation',
            type_service: formData.type_service
        });

        // Google Analytics (si configuré)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'reservation_submitted', {
                'event_category': 'Formulaire',
                'event_label': formData.type_service
            });
        }

    } catch (error) {
        console.error('Erreur lors de l\'envoi:', error);

        showFormError(form, 'Une erreur est survenue lors de l\'envoi de votre réservation. Veuillez réessayer ou nous contacter directement par téléphone au 01 23 45 67 89.');

        // Tracking: form_error
        trackEvent('form_error', {
            form_name: 'reservation',
            error_message: error.message || 'Unknown error'
        });

        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
}

function showFormError(form, message) {
    const existingError = form.querySelector('.form-submit-error');
    if (existingError) existingError.remove();

    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-submit-error alert alert-danger';
    errorDiv.style.cssText = 'margin-bottom: 1rem; padding: 1rem; background: #f8d7da; color: #721c24; border-radius: 8px;';
    errorDiv.textContent = message;

    form.insertBefore(errorDiv, form.firstChild);

    setTimeout(() => errorDiv.remove(), 15000);
}

// ============================================
// ENVOI EMAILJS
// ============================================

async function sendEmailViaEmailJS(formData) {
    if (EMAILJS_CONFIG.serviceID === 'YOUR_SERVICE_ID') {
        log('EmailJS non configuré');
        throw new Error('EmailJS non configuré');
    }

    return emailjs.send(
        EMAILJS_CONFIG.serviceID,
        EMAILJS_CONFIG.templateID,
        formData
    );
}

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

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

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
