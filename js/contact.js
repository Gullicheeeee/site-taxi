/**
 * TAXI JULIEN - Formulaire de Contact
 * Gestion de l'envoi du formulaire de contact
 *
 * FONCTIONNALITÉS:
 * - Tracking GTM pour analyse des conversions
 * - Validation robuste avec feedback visuel
 */

// ============================================
// FLAG DEBUG
// ============================================
const DEBUG = false;

function log(...args) {
    if (DEBUG) console.log('[Contact]', ...args);
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

// ============================================
// GESTION DU FORMULAIRE
// ============================================

let formStarted = false;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const confirmation = document.getElementById('contact-confirmation');
    const telephoneInput = document.getElementById('telephone');

    // Configurer clavier numérique pour téléphone sur mobile
    if (telephoneInput) {
        telephoneInput.setAttribute('inputmode', 'tel');
        telephoneInput.setAttribute('autocomplete', 'tel');
    }

    // Tracking: form_start au premier focus
    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                if (!formStarted) {
                    formStarted = true;
                    trackEvent('form_start', {
                        form_name: 'contact',
                        page: 'contact'
                    });
                }
            }, { once: true });
        });

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            await handleContactSubmit(form, confirmation);
        });
    }

    log('Formulaire de contact initialisé');
});

// ============================================
// VALIDATION DU FORMULAIRE
// ============================================

function validateContactForm(form) {
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

    // Validation email
    const email = document.getElementById('email');
    if (!validateEmail(email.value)) {
        showFieldError(email, 'Adresse email invalide');
        errors.push('email');
        isValid = false;
    } else {
        removeFieldError(email);
    }

    // Validation téléphone (optionnel mais si rempli, doit être valide)
    const telephone = document.getElementById('telephone');
    if (telephone.value.trim() && !validatePhone(telephone.value)) {
        showFieldError(telephone, 'Numéro de téléphone invalide');
        errors.push('telephone');
        isValid = false;
    } else {
        removeFieldError(telephone);
    }

    // Validation sujet
    const sujet = document.getElementById('sujet');
    if (!sujet.value) {
        showFieldError(sujet, 'Veuillez sélectionner un sujet');
        errors.push('sujet');
        isValid = false;
    } else {
        removeFieldError(sujet);
    }

    // Validation message
    const message = document.getElementById('message');
    if (!message.value.trim() || message.value.trim().length < 10) {
        showFieldError(message, 'Veuillez entrer un message (minimum 10 caractères)');
        errors.push('message');
        isValid = false;
    } else {
        removeFieldError(message);
    }

    // Tracking des erreurs de validation
    if (!isValid) {
        trackEvent('form_validation_error', {
            form_name: 'contact',
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

async function handleContactSubmit(form, confirmation) {
    // Validation du formulaire
    if (!validateContactForm(form)) {
        // Scroll vers la première erreur
        const firstError = form.querySelector('.error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
    }

    // Récupérer le bouton submit
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;

    // Désactiver le bouton et afficher un loader
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<div class="spinner"></div> Envoi en cours...';

    // Données du formulaire
    const formData = {
        nom: document.getElementById('nom').value,
        prenom: document.getElementById('prenom').value,
        email: document.getElementById('email').value,
        telephone: document.getElementById('telephone').value || 'Non renseigné',
        sujet: document.getElementById('sujet').value,
        message: document.getElementById('message').value,
        date: new Date().toLocaleString('fr-FR')
    };

    try {
        // Envoyer via EmailJS
        if (typeof emailjs !== 'undefined' && EMAILJS_CONFIG.serviceID !== 'YOUR_SERVICE_ID') {
            await emailjs.send(
                EMAILJS_CONFIG.serviceID,
                EMAILJS_CONFIG.templateID,
                formData
            );
        } else {
            // Mode démo
            log('Mode démo - Données du contact:', formData);
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        // Afficher confirmation
        form.style.display = 'none';
        confirmation.style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Tracking: form_submit
        trackEvent('form_submit', {
            form_name: 'contact',
            sujet: formData.sujet
        });

        // Google Analytics (si configuré)
        if (typeof gtag !== 'undefined') {
            gtag('event', 'contact_submitted', {
                'event_category': 'Formulaire',
                'event_label': formData.sujet
            });
        }

    } catch (error) {
        console.error('Erreur lors de l\'envoi:', error);

        showFormError(form, 'Une erreur est survenue. Veuillez réessayer ou nous contacter directement par téléphone.');

        // Tracking: form_error
        trackEvent('form_error', {
            form_name: 'contact',
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
