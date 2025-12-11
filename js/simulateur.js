/**
 * TAXI JULIEN - Simulateur de Prix
 * Calcul précis des tarifs avec Google Maps Distance Matrix API
 *
 * LOGIQUE MÉTIER:
 * - Tarifs A/B/C/D selon jour/heure
 * - Forfaits pour destinations courantes (aéroport, gares)
 * - Fourchette de prix (+/- 10%) pour conditions variables
 */

// ============================================
// FLAG DEBUG - Mettre à false en production
// ============================================
const DEBUG = false;

function log(...args) {
    if (DEBUG) console.log('[Simulateur]', ...args);
}

// ============================================
// CONFIGURATION GOOGLE MAPS API
// ============================================

// À CONFIGURER : Remplacez par votre clé API Google Maps
const GOOGLE_MAPS_API_KEY = 'YOUR_GOOGLE_MAPS_API_KEY';

// ============================================
// GRILLE TARIFAIRE
// ============================================

const TARIFS = {
    // Tarif minimal de course
    minimum: 8.00,

    // Prise en charge
    priseEnCharge: 2.35,

    // Tarifs au kilomètre
    tarifA: 1.11,  // Jour semaine (7h-19h, lundi-vendredi)
    tarifB: 1.44,  // Nuit semaine (19h-7h, lundi-vendredi)
    tarifC: 2.22,  // Jour weekend (7h-19h, samedi-dimanche-fériés)
    tarifD: 2.88,  // Nuit weekend (19h-7h, samedi-dimanche-fériés)

    // Attente
    attenteHeure: 34.60,

    // Horaires
    heureDebutNuit: 19,  // 19h00
    heureFinNuit: 7,     // 07h00

    // Forfaits spéciaux
    forfaits: {
        // Aéroport Marseille
        'aeroport_marseille_jour': 80.00,
        'aeroport_marseille_nuit': 100.00,

        // Aix TGV
        'aix_tgv_jour': 80.00,
        'aix_tgv_nuit': 100.00,

        // Gare Saint-Charles
        'gare_st_charles_jour': 95.00,
        'gare_st_charles_nuit': 120.00
    },

    // Marge pour fourchette de prix (conditions de circulation variables)
    margeMin: 0.90,  // -10%
    margeMax: 1.10   // +10%
};

// Jours fériés français 2024-2025 (à mettre à jour chaque année)
const JOURS_FERIES = [
    '2024-01-01', '2024-04-01', '2024-05-01', '2024-05-08', '2024-05-09',
    '2024-05-20', '2024-07-14', '2024-08-15', '2024-11-01', '2024-11-11',
    '2024-12-25',
    '2025-01-01', '2025-04-21', '2025-05-01', '2025-05-08', '2025-05-29',
    '2025-06-09', '2025-07-14', '2025-08-15', '2025-11-01', '2025-11-11',
    '2025-12-25'
];

// ============================================
// INITIALISATION
// ============================================

let autocompleteDepart = null;
let autocompleteArrivee = null;
let simulationStarted = false;

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('simulator-form');
    const dateInput = document.getElementById('date');
    const heureInput = document.getElementById('heure');
    const attenteCheckbox = document.getElementById('attente');
    const attenteGroup = document.getElementById('attente-group');
    const departInput = document.getElementById('depart');
    const arriveeInput = document.getElementById('arrivee');

    // Définir la date d'aujourd'hui par défaut
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        dateInput.value = today;
    }

    // Définir l'heure actuelle par défaut
    if (heureInput) {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        heureInput.value = `${hours}:${minutes}`;
    }

    // Afficher/masquer le champ durée d'attente
    if (attenteCheckbox && attenteGroup) {
        attenteCheckbox.addEventListener('change', function() {
            attenteGroup.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Soumission du formulaire
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            calculerPrix();
        });

        // Tracking: simulation_start quand l'utilisateur commence à interagir
        [departInput, arriveeInput].forEach(input => {
            if (input) {
                input.addEventListener('focus', function() {
                    if (!simulationStarted) {
                        simulationStarted = true;
                        trackEvent('simulation_start', {
                            page: 'simulateur'
                        });
                    }
                }, { once: true });
            }
        });
    }

    // Initialiser Google Places Autocomplete si l'API est chargée
    initGooglePlacesAutocomplete();

    // Restaurer les données sauvegardées
    restoreSimulatorData();

    // Sauvegarder les données à chaque modification
    setupAutoSave();

    log('Simulateur initialisé');
});

// ============================================
// GOOGLE PLACES AUTOCOMPLETE
// ============================================

function initGooglePlacesAutocomplete() {
    // Vérifier si l'API Google est chargée
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
        log('Google Places API non chargée - mode dégradé');
        return;
    }

    const departInput = document.getElementById('depart');
    const arriveeInput = document.getElementById('arrivee');

    // Options pour la France et les environs de Martigues
    const options = {
        componentRestrictions: { country: 'fr' },
        fields: ['formatted_address', 'geometry', 'name'],
        types: ['geocode', 'establishment']
    };

    // Biais vers la région de Martigues (Bouches-du-Rhône)
    const martiguesBounds = new google.maps.LatLngBounds(
        new google.maps.LatLng(43.25, 4.75),  // SW
        new google.maps.LatLng(43.55, 5.45)   // NE
    );

    try {
        if (departInput) {
            autocompleteDepart = new google.maps.places.Autocomplete(departInput, options);
            autocompleteDepart.setBounds(martiguesBounds);

            autocompleteDepart.addListener('place_changed', function() {
                const place = autocompleteDepart.getPlace();
                if (place && place.formatted_address) {
                    departInput.value = place.formatted_address;
                    saveSimulatorData();
                }
            });
        }

        if (arriveeInput) {
            autocompleteArrivee = new google.maps.places.Autocomplete(arriveeInput, options);
            autocompleteArrivee.setBounds(martiguesBounds);

            autocompleteArrivee.addListener('place_changed', function() {
                const place = autocompleteArrivee.getPlace();
                if (place && place.formatted_address) {
                    arriveeInput.value = place.formatted_address;
                    saveSimulatorData();
                }
            });
        }

        log('Google Places Autocomplete initialisé');
    } catch (error) {
        console.error('Erreur initialisation Google Places:', error);
    }
}

// Fonction globale appelée par le callback Google Maps
window.initGooglePlaces = function() {
    initGooglePlacesAutocomplete();
};

// ============================================
// SAUVEGARDE LOCALSTORAGE
// ============================================

const STORAGE_KEY_SIMULATOR = 'taxijulien_simulator_data';

function saveSimulatorData() {
    try {
        const data = {
            depart: document.getElementById('depart')?.value || '',
            arrivee: document.getElementById('arrivee')?.value || '',
            date: document.getElementById('date')?.value || '',
            heure: document.getElementById('heure')?.value || '',
            attente: document.getElementById('attente')?.checked || false,
            dureeAttente: document.getElementById('duree-attente')?.value || '',
            timestamp: Date.now()
        };
        localStorage.setItem(STORAGE_KEY_SIMULATOR, JSON.stringify(data));
        log('Données simulateur sauvegardées');
    } catch (error) {
        log('Erreur sauvegarde localStorage:', error);
    }
}

function restoreSimulatorData() {
    try {
        const saved = localStorage.getItem(STORAGE_KEY_SIMULATOR);
        if (!saved) return;

        const data = JSON.parse(saved);

        // Ne restaurer que si les données ont moins de 24h
        const maxAge = 24 * 60 * 60 * 1000; // 24 heures
        if (Date.now() - data.timestamp > maxAge) {
            localStorage.removeItem(STORAGE_KEY_SIMULATOR);
            return;
        }

        // Restaurer les valeurs
        if (data.depart) {
            const departInput = document.getElementById('depart');
            if (departInput) departInput.value = data.depart;
        }
        if (data.arrivee) {
            const arriveeInput = document.getElementById('arrivee');
            if (arriveeInput) arriveeInput.value = data.arrivee;
        }
        // Ne pas restaurer date/heure (utiliser l'actuel)

        log('Données simulateur restaurées');
    } catch (error) {
        log('Erreur restauration localStorage:', error);
    }
}

function setupAutoSave() {
    const inputs = ['depart', 'arrivee', 'date', 'heure', 'attente', 'duree-attente'];

    inputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            const eventType = input.type === 'checkbox' ? 'change' : 'input';
            input.addEventListener(eventType, debounce(saveSimulatorData, 500));
        }
    });
}

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
// CALCUL DU PRIX
// ============================================

async function calculerPrix() {
    const depart = document.getElementById('depart').value.trim();
    const arrivee = document.getElementById('arrivee').value.trim();
    const date = document.getElementById('date').value;
    const heure = document.getElementById('heure').value;
    const attente = document.getElementById('attente').checked;
    const dureeAttente = parseInt(document.getElementById('duree-attente').value) || 0;

    const calculateBtn = document.getElementById('calculate-btn');
    const resultDiv = document.getElementById('price-result');

    // Validation des champs
    if (!validateSimulatorInputs(depart, arrivee, date, heure)) {
        return;
    }

    // Désactiver le bouton
    calculateBtn.disabled = true;
    const originalText = calculateBtn.innerHTML;
    calculateBtn.innerHTML = '<div class="spinner"></div> Calcul en cours...';

    try {
        // Vérifier si c'est un forfait spécial
        const forfait = detecterForfait(depart, arrivee, date, heure);

        let distance = 0;
        let duration = 0;
        let prixBase = 0;
        let prixMin = 0;
        let prixMax = 0;
        let breakdown = [];
        let tarifApplique = '';
        let isForfait = false;

        if (forfait) {
            // Appliquer le forfait
            prixBase = forfait.prix;
            prixMin = forfait.prix; // Forfait = prix fixe
            prixMax = forfait.prix;
            tarifApplique = forfait.label;
            breakdown.push(`Forfait ${forfait.label}`);
            isForfait = true;

            // Récupérer quand même la distance pour info
            try {
                const distanceInfo = await getDistanceFromGoogle(depart, arrivee);
                distance = distanceInfo.distance;
                duration = distanceInfo.duration;
            } catch (error) {
                distance = forfait.distanceEstimee || 0;
                duration = forfait.dureeEstimee || 0;
            }

        } else {
            // Calcul normal avec Google Maps
            const distanceInfo = await getDistanceFromGoogle(depart, arrivee);
            distance = distanceInfo.distance;
            duration = distanceInfo.duration;

            // Déterminer le tarif applicable
            const tarifKm = getTarifKm(date, heure);
            tarifApplique = tarifKm.label;

            // Calcul du prix de base
            prixBase = TARIFS.priseEnCharge + (distance * tarifKm.tarif);
            breakdown.push(`Prise en charge : ${TARIFS.priseEnCharge.toFixed(2)} €`);
            breakdown.push(`Distance : ${distance.toFixed(1)} km × ${tarifKm.tarif.toFixed(2)} €/km = ${(distance * tarifKm.tarif).toFixed(2)} €`);

            // Ajouter l'attente si applicable
            if (attente && dureeAttente > 0) {
                const prixAttente = (dureeAttente / 60) * TARIFS.attenteHeure;
                prixBase += prixAttente;
                breakdown.push(`Attente : ${dureeAttente} min × ${(TARIFS.attenteHeure / 60).toFixed(2)} €/min = ${prixAttente.toFixed(2)} €`);
            }

            // Appliquer le tarif minimum
            if (prixBase < TARIFS.minimum) {
                breakdown.push(`Tarif minimum appliqué : ${TARIFS.minimum.toFixed(2)} €`);
                prixBase = TARIFS.minimum;
            }

            // Calcul de la fourchette (conditions de circulation variables)
            prixMin = Math.max(TARIFS.minimum, prixBase * TARIFS.margeMin);
            prixMax = prixBase * TARIFS.margeMax;
        }

        // Afficher les résultats
        afficherResultat(prixBase, prixMin, prixMax, distance, duration, breakdown, tarifApplique, isForfait);

        // Tracking: simulation_complete
        trackEvent('simulation_complete', {
            depart: depart.substring(0, 50),
            arrivee: arrivee.substring(0, 50),
            distance_km: distance.toFixed(1),
            prix_estime: prixBase.toFixed(2),
            tarif_applique: tarifApplique,
            is_forfait: isForfait
        });

        // Sauvegarder les données
        saveSimulatorData();

        // Réactiver le bouton
        calculateBtn.disabled = false;
        calculateBtn.innerHTML = originalText;

    } catch (error) {
        console.error('Erreur lors du calcul:', error);

        showSimulatorError('Une erreur est survenue lors du calcul. Veuillez vérifier les adresses saisies ou utiliser la grille tarifaire ci-dessous pour une estimation.');

        // Tracking: simulation_error
        trackEvent('simulation_error', {
            error_message: error.message || 'Unknown error'
        });

        calculateBtn.disabled = false;
        calculateBtn.innerHTML = originalText;
    }
}

// ============================================
// VALIDATION DES INPUTS
// ============================================

function validateSimulatorInputs(depart, arrivee, date, heure) {
    const departInput = document.getElementById('depart');
    const arriveeInput = document.getElementById('arrivee');
    const dateInput = document.getElementById('date');
    const heureInput = document.getElementById('heure');

    let isValid = true;

    // Validation adresse de départ
    if (!depart || depart.length < 3) {
        showInputError(departInput, 'Veuillez entrer une adresse de départ valide');
        isValid = false;
    } else {
        removeInputError(departInput);
    }

    // Validation adresse d'arrivée
    if (!arrivee || arrivee.length < 3) {
        showInputError(arriveeInput, 'Veuillez entrer une adresse d\'arrivée valide');
        isValid = false;
    } else {
        removeInputError(arriveeInput);
    }

    // Validation date
    if (!date) {
        showInputError(dateInput, 'Veuillez sélectionner une date');
        isValid = false;
    } else {
        const selectedDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (selectedDate < today) {
            showInputError(dateInput, 'La date ne peut pas être dans le passé');
            isValid = false;
        } else {
            removeInputError(dateInput);
        }
    }

    // Validation heure
    if (!heure) {
        showInputError(heureInput, 'Veuillez sélectionner une heure');
        isValid = false;
    } else {
        removeInputError(heureInput);
    }

    return isValid;
}

function showInputError(input, message) {
    if (!input) return;

    input.classList.add('error');
    input.style.borderColor = '#dc3545';

    // Créer ou mettre à jour le message d'erreur
    let errorDiv = input.parentElement.querySelector('.input-error-message');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'input-error-message';
        errorDiv.style.cssText = 'color: #dc3545; font-size: 0.85rem; margin-top: 0.25rem;';
        input.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function removeInputError(input) {
    if (!input) return;

    input.classList.remove('error');
    input.style.borderColor = '';

    const errorDiv = input.parentElement.querySelector('.input-error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function showSimulatorError(message) {
    const form = document.getElementById('simulator-form');
    if (!form) return;

    // Supprimer l'ancienne erreur si elle existe
    const existingError = form.querySelector('.simulator-error');
    if (existingError) existingError.remove();

    const errorDiv = document.createElement('div');
    errorDiv.className = 'simulator-error alert alert-danger';
    errorDiv.style.cssText = 'margin-bottom: 1rem; padding: 1rem; background: #f8d7da; color: #721c24; border-radius: 8px;';
    errorDiv.textContent = message;

    form.insertBefore(errorDiv, form.firstChild);

    // Supprimer après 10 secondes
    setTimeout(() => errorDiv.remove(), 10000);
}

// ============================================
// GOOGLE MAPS DISTANCE MATRIX API
// ============================================

async function getDistanceFromGoogle(origine, destination) {
    // Vérifier si l'API est configurée
    if (GOOGLE_MAPS_API_KEY === 'YOUR_GOOGLE_MAPS_API_KEY') {
        log('Google Maps API non configurée. Utilisation du mode estimation.');
        return estimerDistance(origine, destination);
    }

    const url = `https://maps.googleapis.com/maps/api/distancematrix/json?origins=${encodeURIComponent(origine)}&destinations=${encodeURIComponent(destination)}&mode=driving&language=fr&key=${GOOGLE_MAPS_API_KEY}`;

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.status === 'OK' && data.rows[0].elements[0].status === 'OK') {
            const element = data.rows[0].elements[0];
            return {
                distance: element.distance.value / 1000, // Convertir en km
                duration: Math.round(element.duration.value / 60) // Convertir en minutes
            };
        } else {
            throw new Error('Impossible de calculer la distance');
        }
    } catch (error) {
        console.error('Erreur API Google Maps:', error);
        // Fallback sur estimation
        return estimerDistance(origine, destination);
    }
}

/**
 * Estimation de distance sans API (mode dégradé)
 */
function estimerDistance(origine, destination) {
    // Distances approximatives pour les destinations courantes depuis Martigues
    const distances = {
        'aeroport': { distance: 45, duration: 35 },
        'marseille provence': { distance: 45, duration: 35 },
        'marseille': { distance: 45, duration: 35 },
        'aix': { distance: 35, duration: 30 },
        'salon': { distance: 25, duration: 25 },
        'istres': { distance: 15, duration: 15 },
        'fos': { distance: 12, duration: 12 },
        'port de bouc': { distance: 8, duration: 10 },
        'saint charles': { distance: 50, duration: 40 },
        'st charles': { distance: 50, duration: 40 },
        'gare': { distance: 50, duration: 40 }
    };

    const dest = destination.toLowerCase();

    for (const [key, value] of Object.entries(distances)) {
        if (dest.includes(key)) {
            return value;
        }
    }

    // Par défaut, estimation moyenne
    return { distance: 20, duration: 20 };
}

// ============================================
// DÉTECTION FORFAIT
// ============================================

function detecterForfait(depart, arrivee, date, heure) {
    const departNorm = normaliserAdresse(depart);
    const arriveeNorm = normaliserAdresse(arrivee);
    const estNuit = isNuit(heure);

    // Vérifier si départ ou arrivée contient "martigues"
    const depuisMartigues = departNorm.includes('martigues');
    const versMartigues = arriveeNorm.includes('martigues');

    // Aéroport Marseille
    if ((depuisMartigues || versMartigues) &&
        (arriveeNorm.includes('aeroport') || arriveeNorm.includes('aéroport') ||
         arriveeNorm.includes('marseille provence') ||
         departNorm.includes('aeroport') || departNorm.includes('aéroport') ||
         departNorm.includes('marseille provence'))) {
        return {
            prix: estNuit ? TARIFS.forfaits.aeroport_marseille_nuit : TARIFS.forfaits.aeroport_marseille_jour,
            label: `Martigues ↔ Aéroport Marseille (${estNuit ? 'Nuit' : 'Jour'})`,
            distanceEstimee: 45,
            dureeEstimee: 35
        };
    }

    // Aix TGV
    if ((depuisMartigues || versMartigues) &&
        (arriveeNorm.includes('aix') && (arriveeNorm.includes('tgv') || arriveeNorm.includes('gare')) ||
         departNorm.includes('aix') && (departNorm.includes('tgv') || departNorm.includes('gare')))) {
        return {
            prix: estNuit ? TARIFS.forfaits.aix_tgv_nuit : TARIFS.forfaits.aix_tgv_jour,
            label: `Martigues ↔ Aix TGV (${estNuit ? 'Nuit' : 'Jour'})`,
            distanceEstimee: 35,
            dureeEstimee: 30
        };
    }

    // Gare Saint-Charles Marseille
    if ((depuisMartigues || versMartigues) &&
        (arriveeNorm.includes('saint') && arriveeNorm.includes('charles') ||
         arriveeNorm.includes('st') && arriveeNorm.includes('charles') ||
         departNorm.includes('saint') && departNorm.includes('charles') ||
         departNorm.includes('st') && departNorm.includes('charles'))) {
        return {
            prix: estNuit ? TARIFS.forfaits.gare_st_charles_nuit : TARIFS.forfaits.gare_st_charles_jour,
            label: `Martigues ↔ Gare St-Charles (${estNuit ? 'Nuit' : 'Jour'})`,
            distanceEstimee: 50,
            dureeEstimee: 40
        };
    }

    return null;
}

function normaliserAdresse(adresse) {
    return adresse.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, ''); // Retirer les accents
}

// ============================================
// DÉTERMINATION DU TARIF
// ============================================

function getTarifKm(date, heure) {
    const estWeekend = isWeekendOuFerie(date);
    const estNuit = isNuit(heure);

    let tarif, label;

    if (estWeekend) {
        if (estNuit) {
            tarif = TARIFS.tarifD;
            label = 'Tarif D - Nuit weekend/férié';
        } else {
            tarif = TARIFS.tarifC;
            label = 'Tarif C - Jour weekend/férié';
        }
    } else {
        if (estNuit) {
            tarif = TARIFS.tarifB;
            label = 'Tarif B - Nuit semaine';
        } else {
            tarif = TARIFS.tarifA;
            label = 'Tarif A - Jour semaine';
        }
    }

    return { tarif, label };
}

/**
 * Vérifie si l'heure est dans la période nuit (19h-7h)
 */
function isNuit(heure) {
    const [heures] = heure.split(':').map(Number);
    return heures >= TARIFS.heureDebutNuit || heures < TARIFS.heureFinNuit;
}

/**
 * Vérifie si c'est un weekend ou jour férié
 */
function isWeekendOuFerie(dateStr) {
    const date = new Date(dateStr);
    const dayOfWeek = date.getDay();

    // Samedi (6) ou Dimanche (0)
    if (dayOfWeek === 0 || dayOfWeek === 6) {
        return true;
    }

    // Jour férié
    if (JOURS_FERIES.includes(dateStr)) {
        return true;
    }

    return false;
}

// ============================================
// AFFICHAGE DES RÉSULTATS
// ============================================

function afficherResultat(prix, prixMin, prixMax, distance, duration, breakdown, tarifApplique, isForfait) {
    const resultDiv = document.getElementById('price-result');
    const priceAmount = document.getElementById('price-amount');
    const distanceValue = document.getElementById('distance-value');
    const durationValue = document.getElementById('duration-value');
    const tarifInfo = document.getElementById('tarif-applique');
    const breakdownContent = document.getElementById('breakdown-content');

    // Prix avec fourchette
    if (isForfait) {
        priceAmount.innerHTML = window.TaxiJulien.formatPrice(prix);
    } else {
        priceAmount.innerHTML = `
            <span style="font-size: 0.7em; display: block; margin-bottom: 0.25rem;">Estimation</span>
            ${window.TaxiJulien.formatPrice(prixMin)} - ${window.TaxiJulien.formatPrice(prixMax)}
        `;
    }

    // Distance et durée
    distanceValue.textContent = `${distance.toFixed(1)} km`;
    durationValue.textContent = `${duration} min`;

    // Tarif appliqué
    tarifInfo.textContent = `Tarif appliqué : ${tarifApplique}`;

    // Détail du calcul
    breakdownContent.innerHTML = breakdown.map(line =>
        `<div style="margin-bottom: 0.5rem; color: white;">• ${line}</div>`
    ).join('');

    // Total
    if (isForfait) {
        breakdownContent.innerHTML += `<div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.3); font-weight: 700; font-size: 1.05rem; color: white;">
            <strong>FORFAIT : ${window.TaxiJulien.formatPrice(prix)}</strong>
        </div>`;
    } else {
        breakdownContent.innerHTML += `<div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.3); font-weight: 700; font-size: 1.05rem; color: white;">
            <strong>ESTIMATION : ${window.TaxiJulien.formatPrice(prixMin)} - ${window.TaxiJulien.formatPrice(prixMax)}</strong>
            <div style="font-weight: 400; font-size: 0.85rem; margin-top: 0.5rem; opacity: 0.9;">
                * La fourchette tient compte des conditions de circulation variables
            </div>
        </div>`;
    }

    // Afficher le résultat
    resultDiv.classList.add('show');
    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// ============================================
// UTILITAIRES
// ============================================

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
