# 🔧 Guide de Configuration Rapide

## ⚡ Configuration en 3 Étapes

### Étape 1 : EmailJS (pour les formulaires)

1. Créez un compte sur [EmailJS.com](https://www.emailjs.com/)
2. Créez un service email (Gmail, Outlook, etc.)
3. Créez un template avec ces variables :
   - Réservation : `{{nom}}`, `{{prenom}}`, `{{email}}`, `{{telephone}}`, `{{type_service}}`, `{{adresse_depart}}`, `{{adresse_arrivee}}`, `{{date_course}}`, `{{heure_course}}`, `{{nb_passagers}}`, `{{commentaire}}`
   - Contact : `{{nom}}`, `{{prenom}}`, `{{email}}`, `{{telephone}}`, `{{sujet}}`, `{{message}}`

4. **Modifiez ces fichiers :**

**js/reservation.js** (ligne 9) :
```javascript
const EMAILJS_CONFIG = {
    serviceID: 'service_xxxxxxx',    // Votre Service ID
    templateID: 'template_xxxxxxx',  // Votre Template ID
    publicKey: 'xxxxxxxxxxxxxx'      // Votre Public Key
};
```

**js/contact.js** (ligne 7) :
```javascript
const EMAILJS_CONFIG = {
    serviceID: 'service_xxxxxxx',
    templateID: 'template_xxxxxxx',
    publicKey: 'xxxxxxxxxxxxxx'
};
```

---

### Étape 2 : Google Maps API (pour le simulateur)

1. Créez un projet sur [Google Cloud Console](https://console.cloud.google.com/)
2. Activez l'API "Distance Matrix API"
3. Créez une clé API
4. Sécurisez-la avec votre nom de domaine

**Modifiez ce fichier :**

**js/simulateur.js** (ligne 9) :
```javascript
const GOOGLE_MAPS_API_KEY = 'AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXX';
```

---

### Étape 3 : Coordonnées du Taxi

**Remplacez dans TOUS les fichiers HTML :**

- `01 23 45 67 89` → **Votre vrai numéro de téléphone**
- `+33123456789` → **Votre numéro WhatsApp** (format international)
- `contact@taxijulien.fr` → **Votre email professionnel**

**Fichiers à modifier :**
- index.html
- reservation.html
- simulateur.html
- conventionne.html
- services.html
- a-propos.html
- contact.html
- mentions-legales.html

**Outil de recherche/remplacement :**
- VS Code : Ctrl+H (Windows) ou Cmd+H (Mac)
- Rechercher : `01 23 45 67 89`
- Remplacer par : `Votre numéro`

---

## ✅ Checklist de Déploiement

Avant de mettre le site en ligne :

- [ ] Configuration EmailJS terminée
- [ ] Configuration Google Maps API terminée
- [ ] Tous les numéros de téléphone remplacés
- [ ] Email professionnel remplacé partout
- [ ] Mentions légales complétées (SIRET, licence, assurance)
- [ ] Images ajoutées (logo, véhicule, chauffeur)
- [ ] Favicon ajouté
- [ ] Test des formulaires de réservation et contact
- [ ] Test du simulateur de prix
- [ ] Test sur mobile
- [ ] Vérification de tous les liens

---

## 🚨 Problèmes Fréquents

### "Le formulaire ne s'envoie pas"
→ Vérifiez que les 3 identifiants EmailJS sont bien renseignés

### "Le simulateur affiche une erreur"
→ Vérifiez que la clé Google Maps API est activée et correcte

### "Les prix ne sont pas bons"
→ Modifiez les tarifs dans `js/simulateur.js` lignes 22-39

---

## 📧 Besoin d'Aide ?

Si vous rencontrez des difficultés, contactez votre développeur web ou consultez le fichier README.md pour plus de détails.

Bon courage ! 🚖
