/**
 * TAXI JULIEN - Script Principal
 * Gestion de la navigation, animations et interactions de base
 */

// ============================================
// NAVIGATION MOBILE
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    // Toggle menu mobile
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');

            // Animation de l'icône
            if (navMenu.classList.contains('active')) {
                this.textContent = '✕';
            } else {
                this.textContent = '☰';
            }
        });
    }

    // Gestion des dropdowns sur mobile
    const navItems = document.querySelectorAll('.nav-item');

    navItems.forEach(item => {
        const link = item.querySelector('.nav-link');
        const dropdown = item.querySelector('.nav-dropdown');

        if (link && dropdown) {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    item.classList.toggle('mobile-open');
                }
            });
        }
    });

    // Fermer le menu lors du clic sur un lien (sauf les liens avec dropdown)
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Ne pas fermer si c'est un lien avec dropdown
            if (this.parentElement.classList.contains('nav-item') &&
                this.parentElement.querySelector('.nav-dropdown')) {
                return;
            }

            if (window.innerWidth <= 768) {
                navMenu.classList.remove('active');
                if (mobileMenuToggle) {
                    mobileMenuToggle.textContent = '☰';
                }
            }
        });
    });

    // Fermer le menu lors du clic sur un item du dropdown
    const dropdownItems = document.querySelectorAll('.nav-dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                navMenu.classList.remove('active');
                if (mobileMenuToggle) {
                    mobileMenuToggle.textContent = '☰';
                }
                // Fermer tous les dropdowns ouverts
                navItems.forEach(navItem => {
                    navItem.classList.remove('mobile-open');
                });
            }
        });
    });

    // Fermer le menu si clic en dehors
    document.addEventListener('click', function(event) {
        if (navMenu && navMenu.classList.contains('active')) {
            if (!navMenu.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                navMenu.classList.remove('active');
                if (mobileMenuToggle) {
                    mobileMenuToggle.textContent = '☰';
                }
            }
        }
    });
});

// ============================================
// SCROLL HEADER (ombre au scroll)
// ============================================

window.addEventListener('scroll', function() {
    const header = document.querySelector('.header');
    if (window.scrollY > 50) {
        header.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    } else {
        header.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
    }
});

// ============================================
// ANIMATIONS AU SCROLL
// ============================================

const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('fade-in-up');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Appliquer l'animation aux cards
document.querySelectorAll('.card, .feature-item').forEach(el => {
    observer.observe(el);
});

// ============================================
// SMOOTH SCROLL POUR LES ANCRES
// ============================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#' && href !== '') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// ============================================
// VALIDATION FORMULAIRES
// ============================================

/**
 * Valide un champ email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Valide un numéro de téléphone français
 */
function validatePhone(phone) {
    // Accepte formats: 0123456789, 01 23 45 67 89, 01.23.45.67.89, +33123456789
    const cleaned = phone.replace(/[\s\.\-]/g, '');
    const re = /^(\+33|0)[1-9]\d{8}$/;
    return re.test(cleaned);
}

/**
 * Affiche une erreur sur un champ
 */
function showError(input, message) {
    input.classList.add('error');
    let errorElement = input.nextElementSibling;

    if (!errorElement || !errorElement.classList.contains('form-error')) {
        errorElement = document.createElement('div');
        errorElement.classList.add('form-error');
        input.parentNode.insertBefore(errorElement, input.nextSibling);
    }

    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

/**
 * Retire l'erreur d'un champ
 */
function removeError(input) {
    input.classList.remove('error');
    const errorElement = input.nextElementSibling;
    if (errorElement && errorElement.classList.contains('form-error')) {
        errorElement.style.display = 'none';
    }
}

/**
 * Valide un formulaire complet
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('.form-control[required]');

    inputs.forEach(input => {
        removeError(input);

        // Vérification champ vide
        if (!input.value.trim()) {
            showError(input, 'Ce champ est obligatoire');
            isValid = false;
            return;
        }

        // Vérification email
        if (input.type === 'email' && !validateEmail(input.value)) {
            showError(input, 'Adresse email invalide');
            isValid = false;
            return;
        }

        // Vérification téléphone
        if (input.type === 'tel' && !validatePhone(input.value)) {
            showError(input, 'Numéro de téléphone invalide');
            isValid = false;
            return;
        }
    });

    return isValid;
}

// Validation en temps réel
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.hasAttribute('required') && this.value.trim()) {
            if (this.type === 'email' && !validateEmail(this.value)) {
                showError(this, 'Adresse email invalide');
            } else if (this.type === 'tel' && !validatePhone(this.value)) {
                showError(this, 'Numéro de téléphone invalide');
            } else {
                removeError(this);
            }
        }
    });

    input.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            removeError(this);
        }
    });
});

// ============================================
// UTILITAIRES
// ============================================

/**
 * Affiche un message de succès
 */
function showSuccessMessage(message, container) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.textContent = message;
    container.insertBefore(alert, container.firstChild);

    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

/**
 * Affiche un message d'erreur
 */
function showErrorMessage(message, container) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger';
    alert.textContent = message;
    container.insertBefore(alert, container.firstChild);

    setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

/**
 * Formate un numéro de téléphone
 */
function formatPhoneNumber(phone) {
    const cleaned = phone.replace(/[\s\.\-]/g, '');

    if (cleaned.startsWith('+33')) {
        return cleaned;
    } else if (cleaned.startsWith('0')) {
        return cleaned.replace(/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
    }

    return phone;
}

/**
 * Formate un prix
 */
function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2
    }).format(price);
}

/**
 * Calcul de distance entre deux points (formule de Haversine)
 * Utile en l'absence d'API Google Maps
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Rayon de la Terre en km
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon / 2) * Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    const distance = R * c;

    return distance;
}

function toRad(degrees) {
    return degrees * (Math.PI / 180);
}

// ============================================
// LAZY LOADING IMAGES (Performance SEO)
// ============================================

/**
 * Implémentation du lazy loading natif avec fallback
 */
function initLazyLoading() {
    // Ajouter loading="lazy" à toutes les images sans cet attribut
    document.querySelectorAll('img:not([loading])').forEach(img => {
        // Ne pas appliquer aux images dans le viewport initial
        const rect = img.getBoundingClientRect();
        if (rect.top > window.innerHeight) {
            img.setAttribute('loading', 'lazy');
            img.setAttribute('decoding', 'async');
        }
    });

    // Fallback pour les navigateurs qui ne supportent pas loading="lazy"
    if (!('loading' in HTMLImageElement.prototype)) {
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');

        const lazyLoad = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '200px 0px'
        });

        lazyImages.forEach(img => lazyLoad.observe(img));
    }
}

// Initialiser le lazy loading au chargement
document.addEventListener('DOMContentLoaded', initLazyLoading);

// ============================================
// PRELOAD CRITICAL RESOURCES
// ============================================

/**
 * Preload des ressources critiques pour les liens survolés
 */
function initLinkPreload() {
    const preloadedUrls = new Set();

    document.querySelectorAll('a[href]').forEach(link => {
        link.addEventListener('mouseenter', function() {
            const href = this.getAttribute('href');

            // Ne preload que les liens internes non-ancres
            if (href &&
                !href.startsWith('#') &&
                !href.startsWith('tel:') &&
                !href.startsWith('mailto:') &&
                !href.startsWith('http') &&
                !preloadedUrls.has(href)) {

                const preloadLink = document.createElement('link');
                preloadLink.rel = 'prefetch';
                preloadLink.href = href;
                document.head.appendChild(preloadLink);
                preloadedUrls.add(href);
            }
        }, { once: true });
    });
}

document.addEventListener('DOMContentLoaded', initLinkPreload);

// ============================================
// GESTION DES COOKIES (RGPD)
// ============================================

/**
 * Vérifie si l'utilisateur a accepté les cookies
 */
function checkCookieConsent() {
    return localStorage.getItem('cookieConsent') === 'accepted';
}

/**
 * Enregistre le consentement
 */
function setCookieConsent() {
    localStorage.setItem('cookieConsent', 'accepted');
}

// ============================================
// EXPORT DES FONCTIONS UTILES
// ============================================

window.TaxiJulien = {
    validateEmail,
    validatePhone,
    validateForm,
    showSuccessMessage,
    showErrorMessage,
    formatPhoneNumber,
    formatPrice,
    calculateDistance
};
