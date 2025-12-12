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
            const isOpen = navMenu.classList.toggle('active');

            // Update ARIA attributes
            this.setAttribute('aria-expanded', isOpen);
            this.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');

            // Animation de l'icône
            const iconSpan = this.querySelector('span') || this;
            if (isOpen) {
                if (iconSpan.tagName === 'SPAN') {
                    iconSpan.textContent = '✕';
                } else {
                    this.textContent = '✕';
                }
            } else {
                if (iconSpan.tagName === 'SPAN') {
                    iconSpan.textContent = '☰';
                } else {
                    this.textContent = '☰';
                }
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

    // Helper function to close mobile menu
    function closeMobileMenu() {
        if (navMenu && mobileMenuToggle) {
            navMenu.classList.remove('active');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            mobileMenuToggle.setAttribute('aria-label', 'Ouvrir le menu');
            const iconSpan = mobileMenuToggle.querySelector('span');
            if (iconSpan) {
                iconSpan.textContent = '☰';
            } else {
                mobileMenuToggle.textContent = '☰';
            }
        }
    }

    // Fermer le menu lors du clic sur un lien (sauf les liens avec dropdown)
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Ne pas fermer si c'est un lien avec dropdown
            if (this.parentElement.classList.contains('nav-item') &&
                this.parentElement.querySelector('.nav-dropdown')) {
                return;
            }

            if (window.innerWidth <= 768) {
                closeMobileMenu();
            }
        });
    });

    // Fermer le menu lors du clic sur un item du dropdown
    const dropdownItems = document.querySelectorAll('.nav-dropdown-item');
    dropdownItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeMobileMenu();
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
                closeMobileMenu();
            }
        }
    });

    // Support clavier pour fermer le menu avec Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && navMenu && navMenu.classList.contains('active')) {
            closeMobileMenu();
            mobileMenuToggle.focus();
        }
    });
});

// ============================================
// SCROLL HEADER (shrink effect - header toujours visible)
// ============================================

(function() {
    const header = document.querySelector('.header');
    if (!header) return;

    let ticking = false;
    const scrollThreshold = 100;

    function updateHeader() {
        const currentScrollY = window.scrollY;

        // Add/remove scrolled class for shrink effect
        if (currentScrollY > scrollThreshold) {
            header.classList.add('header--scrolled');
        } else {
            header.classList.remove('header--scrolled');
        }

        // Header reste TOUJOURS visible (plus de hide sur scroll)
        ticking = false;
    }

    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }, { passive: true });
})();

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

// ============================================
// CARDS SLIDER SYSTEM
// ============================================

/**
 * Système de slider adaptatif pour les grilles de cartes
 * - ≤ 4 cartes : Grille centrée
 * - > 4 cartes : Slider horizontal avec peek effect
 */
class CardsSlider {
    constructor(container) {
        this.container = container;
        this.cards = Array.from(container.querySelectorAll('.card'));
        this.cardsPerView = this.getCardsPerView();
        this.currentIndex = 0;
        this.maxIndex = Math.max(0, this.cards.length - this.cardsPerView);

        // Ne pas initialiser le slider si ≤ 4 cartes sur desktop
        if (this.cards.length <= 4 && window.innerWidth > 768) {
            return;
        }

        // Ne pas initialiser si ≤ 1 carte sur mobile
        if (this.cards.length <= 1) {
            return;
        }

        this.init();
    }

    getCardsPerView() {
        const width = window.innerWidth;
        if (width <= 768) return 1;
        if (width <= 1024) return 3;
        return 4;
    }

    init() {
        // Ajouter classe slider
        this.container.classList.add('cards-slider');

        // Wrapper les cards dans un track
        this.track = document.createElement('div');
        this.track.className = 'cards-track';
        this.cards.forEach(card => this.track.appendChild(card));
        this.container.appendChild(this.track);

        // Créer navigation
        this.createNavigation();

        // Event listeners
        this.bindEvents();

        // État initial
        this.updateSlider();
    }

    createNavigation() {
        const nav = document.createElement('div');
        nav.className = 'cards-nav';
        nav.setAttribute('role', 'group');
        nav.setAttribute('aria-label', 'Navigation du carousel');

        // Bouton précédent
        this.prevBtn = document.createElement('button');
        this.prevBtn.className = 'cards-nav-btn';
        this.prevBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>`;
        this.prevBtn.setAttribute('aria-label', 'Élément précédent');

        // Dots
        this.dotsContainer = document.createElement('div');
        this.dotsContainer.className = 'cards-dots';
        this.dotsContainer.setAttribute('role', 'tablist');

        const totalDots = this.maxIndex + 1;
        for (let i = 0; i < totalDots; i++) {
            const dot = document.createElement('button');
            dot.className = 'cards-dot';
            dot.setAttribute('aria-label', `Aller au groupe ${i + 1}`);
            dot.setAttribute('role', 'tab');
            dot.dataset.index = i;
            this.dotsContainer.appendChild(dot);
        }

        // Bouton suivant
        this.nextBtn = document.createElement('button');
        this.nextBtn.className = 'cards-nav-btn';
        this.nextBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>`;
        this.nextBtn.setAttribute('aria-label', 'Élément suivant');

        nav.appendChild(this.prevBtn);
        nav.appendChild(this.dotsContainer);
        nav.appendChild(this.nextBtn);

        this.container.parentNode.insertBefore(nav, this.container.nextSibling);
        this.nav = nav;
    }

    bindEvents() {
        // Boutons
        this.prevBtn.addEventListener('click', () => this.prev());
        this.nextBtn.addEventListener('click', () => this.next());

        // Dots
        this.dotsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('cards-dot')) {
                this.goTo(parseInt(e.target.dataset.index));
            }
        });

        // Resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => this.handleResize(), 150);
        });

        // Touch/Swipe
        this.handleTouch();

        // Keyboard
        this.container.setAttribute('tabindex', '0');
        this.container.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.prev();
            }
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.next();
            }
        });
    }

    handleTouch() {
        let startX, startY, isDragging = false;

        this.track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isDragging = true;
        }, { passive: true });

        this.track.addEventListener('touchmove', (e) => {
            if (!isDragging) return;

            const deltaX = e.touches[0].clientX - startX;
            const deltaY = e.touches[0].clientY - startY;

            // Si scroll horizontal > vertical, empêcher scroll page
            if (Math.abs(deltaX) > Math.abs(deltaY)) {
                e.preventDefault();
            }
        }, { passive: false });

        this.track.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            isDragging = false;

            const deltaX = e.changedTouches[0].clientX - startX;
            const threshold = 50;

            if (deltaX > threshold) {
                this.prev();
            } else if (deltaX < -threshold) {
                this.next();
            }
        }, { passive: true });
    }

    handleResize() {
        const newCardsPerView = this.getCardsPerView();

        // Désactiver le slider si ≤ 4 cartes sur desktop après resize
        if (this.cards.length <= 4 && window.innerWidth > 768) {
            this.destroy();
            return;
        }

        // Recalculer si le nombre de cartes visibles change
        if (newCardsPerView !== this.cardsPerView) {
            this.cardsPerView = newCardsPerView;
            this.maxIndex = Math.max(0, this.cards.length - this.cardsPerView);

            // Ajuster l'index si nécessaire
            if (this.currentIndex > this.maxIndex) {
                this.currentIndex = this.maxIndex;
            }

            // Recréer les dots
            this.dotsContainer.innerHTML = '';
            const totalDots = this.maxIndex + 1;
            for (let i = 0; i < totalDots; i++) {
                const dot = document.createElement('button');
                dot.className = 'cards-dot';
                dot.setAttribute('aria-label', `Aller au groupe ${i + 1}`);
                dot.dataset.index = i;
                this.dotsContainer.appendChild(dot);
            }

            this.updateSlider();
        }
    }

    destroy() {
        // Retirer la classe slider
        this.container.classList.remove('cards-slider');

        // Remettre les cartes directement dans le container
        this.cards.forEach(card => this.container.appendChild(card));

        // Supprimer le track
        if (this.track && this.track.parentNode) {
            this.track.remove();
        }

        // Supprimer la navigation
        if (this.nav && this.nav.parentNode) {
            this.nav.remove();
        }
    }

    prev() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateSlider();
        }
    }

    next() {
        if (this.currentIndex < this.maxIndex) {
            this.currentIndex++;
            this.updateSlider();
        }
    }

    goTo(index) {
        this.currentIndex = Math.max(0, Math.min(index, this.maxIndex));
        this.updateSlider();
    }

    updateSlider() {
        // Calculer le déplacement
        const cardWidth = this.cards[0].offsetWidth;
        const gap = parseInt(getComputedStyle(this.track).gap) || 24;
        const offset = this.currentIndex * (cardWidth + gap);

        this.track.style.transform = `translateX(-${offset}px)`;

        // Mettre à jour boutons
        this.prevBtn.disabled = this.currentIndex === 0;
        this.nextBtn.disabled = this.currentIndex >= this.maxIndex;

        // Mettre à jour dots
        const dots = this.dotsContainer.querySelectorAll('.cards-dot');
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === this.currentIndex);
            dot.setAttribute('aria-selected', i === this.currentIndex);
        });

        // Classe scrolled pour le fade
        this.container.classList.toggle('scrolled', this.currentIndex > 0);
    }
}

/**
 * Initialise les sliders sur toutes les grilles de cartes
 */
function initCardsSliders() {
    const grids = document.querySelectorAll('.cards-grid');

    grids.forEach(grid => {
        const cards = grid.querySelectorAll('.card');

        // Slider seulement si > 4 cartes OU sur mobile avec > 1 carte
        const shouldSlide = cards.length > 4 || (window.innerWidth <= 768 && cards.length > 1);

        if (shouldSlide) {
            new CardsSlider(grid);
        }
    });
}

// Lancer à la fin du chargement
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCardsSliders);
} else {
    initCardsSliders();
}
