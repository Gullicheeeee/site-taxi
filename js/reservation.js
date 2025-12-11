/**
 * TAXI JULIEN - Gestion des Réservations
 * Formulaire de réservation avec envoi via EmailJS et feedback temps réel
 *
 * FONCTIONNALITÉS:
 * - Sauvegarde automatique localStorage (évite perte de données)
 * - Tracking GTM pour analyse des conversions
 * - Validation robuste avec feedback visuel temps réel
 * - Barre de progression du formulaire
 * - Notifications toast élégantes
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
// VALIDATION EN TEMPS RÉEL
// ============================================

const RealtimeValidation = {
    // Configuration des validations par champ
    validators: {
        nom: {
            validate: (value) => value.trim().length >= 2,
            message: 'Le nom doit contenir au moins 2 caractères'
        },
        prenom: {
            validate: (value) => value.trim().length >= 2,
            message: 'Le prénom doit contenir au moins 2 caractères'
        },
        telephone: {
            validate: (value) => {
                const cleaned = value.replace(/[\s\.\-]/g, '');
                return /^(\+33|0)[1-9]\d{8}$/.test(cleaned);
            },
            message: 'Numéro de téléphone invalide'
        },
        email: {
            validate: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            message: 'Adresse email invalide'
        },
        'type-service': {
            validate: (value) => value !== '',
            message: 'Veuillez sélectionner un type de service'
        },
        'adresse-depart': {
            validate: (value) => value.trim().length >= 5,
            message: 'Adresse trop courte (min. 5 caractères)'
        },
        'adresse-arrivee': {
            validate: (value) => value.trim().length >= 5,
            message: 'Adresse trop courte (min. 5 caractères)'
        },
        'date-course': {
            validate: (value) => {
                const date = new Date(value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return date >= today;
            },
            message: 'La date ne peut pas être dans le passé'
        },
        'heure-course': {
            validate: (value) => value !== '',
            message: 'Veuillez sélectionner une heure'
        }
    },

    // Initialiser la validation temps réel
    init(form) {
        if (!form) return;

        const inputs = form.querySelectorAll('.form-control');

        inputs.forEach(input => {
            const fieldName = input.id;
            const validator = this.validators[fieldName];

            if (!validator) return;

            // Validation au blur (quand on quitte le champ)
            input.addEventListener('blur', () => {
                this.validateField(input, validator);
            });

            // Validation pendant la saisie (debounced)
            let timeout;
            input.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if (input.value.trim()) {
                        this.validateField(input, validator);
                    } else {
                        this.clearValidation(input);
                    }
                }, 300);
            });
        });
    },

    validateField(input, validator) {
        const isValid = validator.validate(input.value);

        input.classList.remove('error', 'valid', 'shake');

        // Remove existing error message
        const existingError = input.parentNode.querySelector('.form-error');
        if (existingError) {
            existingError.remove();
        }

        if (isValid) {
            input.classList.add('valid');
        } else if (input.value.trim()) {
            input.classList.add('error', 'shake');

            // Add error message
            const errorEl = document.createElement('div');
            errorEl.className = 'form-error';
            errorEl.textContent = validator.message;
            errorEl.style.display = 'block';
            input.parentNode.appendChild(errorEl);
        }

        return isValid;
    },

    clearValidation(input) {
        input.classList.remove('error', 'valid', 'shake');
        const existingError = input.parentNode.querySelector('.form-error');
        if (existingError) {
            existingError.remove();
        }
    },

    // Valider tout le formulaire
    validateAll(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('.form-control[required]');

        inputs.forEach(input => {
            const validator = this.validators[input.id];
            if (validator) {
                const fieldValid = this.validateField(input, validator);
                if (!fieldValid) isValid = false;
            }
        });

        return isValid;
    }
};

// ============================================
// BARRE DE PROGRESSION DU FORMULAIRE
// ============================================

const FormProgress = {
    sections: ['personal', 'trip', 'additional'],

    init(form) {
        if (!form) return;

        // Créer la barre de progression
        const progressBar = document.createElement('div');
        progressBar.className = 'form-progress';
        progressBar.setAttribute('role', 'progressbar');
        progressBar.setAttribute('aria-label', 'Progression du formulaire');

        this.sections.forEach((section, index) => {
            const step = document.createElement('div');
            step.className = 'form-progress__step';
            step.dataset.section = section;
            progressBar.appendChild(step);
        });

        // Insérer avant le premier h3
        const firstH3 = form.querySelector('h3');
        if (firstH3) {
            form.insertBefore(progressBar, firstH3);
        }

        this.progressBar = progressBar;
        this.updateProgress(form);

        // Mettre à jour au changement
        form.addEventListener('input', () => this.updateProgress(form));
        form.addEventListener('change', () => this.updateProgress(form));
    },

    updateProgress(form) {
        if (!this.progressBar) return;

        // Section 1: Infos personnelles (nom, prenom, telephone, email)
        const personalFields = ['nom', 'prenom', 'telephone', 'email'];
        const personalFilled = personalFields.every(id => {
            const el = form.querySelector(`#${id}`);
            return el && el.value.trim();
        });

        // Section 2: Trajet (type-service, adresse-depart, adresse-arrivee, date-course, heure-course)
        const tripFields = ['type-service', 'adresse-depart', 'adresse-arrivee', 'date-course', 'heure-course'];
        const tripFilled = tripFields.every(id => {
            const el = form.querySelector(`#${id}`);
            return el && el.value.trim();
        });

        // Section 3: RGPD
        const rgpdChecked = form.querySelector('#rgpd-consent')?.checked;

        const steps = this.progressBar.querySelectorAll('.form-progress__step');

        if (steps[0]) {
            steps[0].classList.toggle('form-progress__step--active', personalFilled);
        }
        if (steps[1]) {
            steps[1].classList.toggle('form-progress__step--active', personalFilled && tripFilled);
        }
        if (steps[2]) {
            steps[2].classList.toggle('form-progress__step--active', personalFilled && tripFilled && rgpdChecked);
        }
    }
};

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

    // Initialiser la validation temps réel
    RealtimeValidation.init(form);

    // Initialiser la barre de progression
    FormProgress.init(form);

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

    // Afficher l'info si service conventionné avec animation
    if (typeServiceSelect && infoConventionne) {
        typeServiceSelect.addEventListener('change', function() {
            if (this.value === 'conventionne') {
                infoConventionne.style.display = 'block';
                infoConventionne.style.opacity = '0';
                infoConventionne.style.transform = 'translateY(-10px)';
                requestAnimationFrame(() => {
                    infoConventionne.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    infoConventionne.style.opacity = '1';
                    infoConventionne.style.transform = 'translateY(0)';
                });
            } else {
                infoConventionne.style.opacity = '0';
                infoConventionne.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    infoConventionne.style.display = 'none';
                }, 300);
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
// SOUMISSION DU FORMULAIRE
// ============================================

async function handleFormSubmit(form, submitBtn, confirmationMessage) {
    // Validation complète du formulaire avec feedback temps réel
    if (!RealtimeValidation.validateAll(form)) {
        // Scroll vers la première erreur
        const firstError = form.querySelector('.form-control.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }

        // Tracking des erreurs
        const errorFields = Array.from(form.querySelectorAll('.form-control.error'))
            .map(el => el.id)
            .join(',');
        trackEvent('form_validation_error', {
            form_name: 'reservation',
            error_fields: errorFields
        });
        return;
    }

    // Vérifier le consentement RGPD
    const rgpdConsent = document.getElementById('rgpd-consent');
    if (!rgpdConsent.checked) {
        const rgpdLabel = rgpdConsent.closest('label');
        if (rgpdLabel) {
            rgpdLabel.classList.add('shake');
            setTimeout(() => rgpdLabel.classList.remove('shake'), 500);
        }
        rgpdConsent.focus();
        return;
    }

    // Désactiver le bouton et afficher un loader avec la classe CSS
    submitBtn.disabled = true;
    submitBtn.classList.add('btn--loading');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = 'Envoi en cours...';

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

        // Succès - animation de disparition du formulaire
        form.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        form.style.opacity = '0';
        form.style.transform = 'translateY(-20px)';

        setTimeout(() => {
            form.style.display = 'none';
            confirmationMessage.style.display = 'block';
            confirmationMessage.style.opacity = '0';
            confirmationMessage.style.transform = 'translateY(20px)';

            requestAnimationFrame(() => {
                confirmationMessage.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                confirmationMessage.style.opacity = '1';
                confirmationMessage.style.transform = 'translateY(0)';
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 300);

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

        // Afficher une notification d'erreur élégante
        showNotification('error', 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer ou nous appeler au 01 23 45 67 89.');

        // Tracking: form_error
        trackEvent('form_error', {
            form_name: 'reservation',
            error_message: error.message || 'Unknown error'
        });

        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.classList.remove('btn--loading');
        submitBtn.innerHTML = originalBtnText;
    }
}

// ============================================
// NOTIFICATION TOAST
// ============================================

function showNotification(type, message) {
    // Supprimer les notifications existantes
    const existing = document.querySelector('.notification-toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `notification-toast notification-toast--${type}`;
    toast.innerHTML = `
        <span class="notification-toast__icon">${type === 'error' ? '⚠️' : '✓'}</span>
        <span class="notification-toast__message">${message}</span>
        <button class="notification-toast__close" aria-label="Fermer">×</button>
    `;

    // Styles inline pour la notification (pourrait être en CSS)
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        left: 20px;
        max-width: 500px;
        margin: 0 auto;
        background: ${type === 'error' ? '#fee2e2' : '#d1fae5'};
        color: ${type === 'error' ? '#991b1b' : '#065f46'};
        padding: 1rem 1.5rem;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 10000;
        animation: slideUp 0.3s ease;
    `;

    // Ajouter l'animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    document.body.appendChild(toast);

    // Fermeture
    const closeBtn = toast.querySelector('.notification-toast__close');
    closeBtn.style.cssText = 'background: none; border: none; font-size: 1.5rem; cursor: pointer; margin-left: auto;';
    closeBtn.addEventListener('click', () => toast.remove());

    // Auto-close après 5s
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(100px)';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
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
