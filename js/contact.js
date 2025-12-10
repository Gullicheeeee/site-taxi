/**
 * TAXI JULIEN - Formulaire de Contact
 * Gestion de l'envoi du formulaire de contact
 */

// Configuration EmailJS (mêmes identifiants que reservation.js)
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

// Gestion du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const confirmation = document.getElementById('contact-confirmation');

    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validation
            if (!window.TaxiJulien.validateForm(form)) {
                return;
            }

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
                    console.log('Mode démo - Données du contact:', formData);
                    await new Promise(resolve => setTimeout(resolve, 500));
                }

                // Afficher confirmation
                form.style.display = 'none';
                confirmation.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });

            } catch (error) {
                console.error('Erreur lors de l\'envoi:', error);
                alert('Une erreur est survenue. Veuillez réessayer ou nous contacter directement par téléphone.');
            }
        });
    }
});
