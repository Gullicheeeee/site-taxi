/**
 * TAXI JULIEN - Simulateur de Prix
 * Calcul précis des tarifs avec Google Maps Distance Matrix API
 */

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
    }
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

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('simulator-form');
    const dateInput = document.getElementById('date');
    const heureInput = document.getElementById('heure');
    const attenteCheckbox = document.getElementById('attente');
    const attenteGroup = document.getElementById('attente-group');

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
    }
});

// ============================================
// CALCUL DU PRIX
// ============================================

async function calculerPrix() {
    const depart = document.getElementById('depart').value;
    const arrivee = document.getElementById('arrivee').value;
    const date = document.getElementById('date').value;
    const heure = document.getElementById('heure').value;
    const attente = document.getElementById('attente').checked;
    const dureeAttente = document.getElementById('duree-attente').value || 0;

    const calculateBtn = document.getElementById('calculate-btn');
    const resultDiv = document.getElementById('price-result');

    // Désactiver le bouton
    calculateBtn.disabled = true;
    const originalText = calculateBtn.innerHTML;
    calculateBtn.innerHTML = '<div class="spinner"></div> Calcul en cours...';

    try {
        // Vérifier si c'est un forfait spécial
        const forfait = detecterForfait(depart, arrivee, date, heure);

        let distance = 0;
        let duration = 0;
        let prix = 0;
        let breakdown = [];
        let tarifApplique = '';

        if (forfait) {
            // Appliquer le forfait
            prix = forfait.prix;
            tarifApplique = forfait.label;
            breakdown.push(`Forfait ${forfait.label}`);

            // Récupérer quand même la distance pour info (optionnel)
            try {
                const distanceInfo = await getDistanceFromGoogle(depart, arrivee);
                distance = distanceInfo.distance;
                duration = distanceInfo.duration;
            } catch (error) {
                // Si erreur Google Maps, utiliser des valeurs estimées
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

            // Calcul du prix
            let prixBase = TARIFS.priseEnCharge + (distance * tarifKm.tarif);
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
                prix = TARIFS.minimum;
            } else {
                prix = prixBase;
            }
        }

        // Afficher les résultats
        afficherResultat(prix, distance, duration, breakdown, tarifApplique);

        // Réactiver le bouton
        calculateBtn.disabled = false;
        calculateBtn.innerHTML = originalText;

    } catch (error) {
        console.error('Erreur lors du calcul:', error);

        alert('Une erreur est survenue lors du calcul. Veuillez vérifier les adresses saisies ou utiliser la grille tarifaire ci-dessous pour une estimation.');

        calculateBtn.disabled = false;
        calculateBtn.innerHTML = originalText;
    }
}

// ============================================
// GOOGLE MAPS DISTANCE MATRIX API
// ============================================

async function getDistanceFromGoogle(origine, destination) {
    // Vérifier si l'API est configurée
    if (GOOGLE_MAPS_API_KEY === 'YOUR_GOOGLE_MAPS_API_KEY') {
        console.warn('⚠️ Google Maps API non configurée. Utilisation du mode estimation.');
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
    // Distances approximatives pour les destinations courantes
    const distances = {
        'aeroport': { distance: 45, duration: 35 },
        'marseille': { distance: 45, duration: 35 },
        'aix': { distance: 35, duration: 30 },
        'salon': { distance: 25, duration: 25 },
        'istres': { distance: 15, duration: 15 },
        'fos': { distance: 12, duration: 12 }
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

function afficherResultat(prix, distance, duration, breakdown, tarifApplique) {
    const resultDiv = document.getElementById('price-result');
    const priceAmount = document.getElementById('price-amount');
    const distanceValue = document.getElementById('distance-value');
    const durationValue = document.getElementById('duration-value');
    const tarifInfo = document.getElementById('tarif-applique');
    const breakdownContent = document.getElementById('breakdown-content');

    // Prix
    priceAmount.textContent = window.TaxiJulien.formatPrice(prix);

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
    breakdownContent.innerHTML += `<div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.3); font-weight: 700; font-size: 1.05rem; color: white;">
        <strong>TOTAL : ${window.TaxiJulien.formatPrice(prix)}</strong>
    </div>`;

    // Afficher le résultat
    resultDiv.classList.add('show');
    resultDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
