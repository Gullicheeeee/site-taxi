# 🚖 Site Web Taxi Julien - Taxi Conventionné Martigues

Site web moderne et responsive pour Taxi Julien, taxi conventionné CPAM basé à Martigues.

## 📋 Table des Matières

- [Aperçu](#aperçu)
- [Fonctionnalités](#fonctionnalités)
- [Technologies Utilisées](#technologies-utilisées)
- [Installation](#installation)
- [Configuration](#configuration)
- [Structure du Projet](#structure-du-projet)
- [Personnalisation](#personnalisation)
- [Déploiement](#déploiement)
- [Support](#support)

## 🎯 Aperçu

Ce site propose :
- **Réservation en ligne** de taxi avec formulaire complet
- **Simulateur de prix** précis avec intégration Google Maps
- **Informations complètes** sur le service de transport conventionné CPAM
- **Design moderne et responsive** optimisé mobile-first
- **SEO optimisé** pour le référencement local

## ✨ Fonctionnalités

### Pages du Site
- ✅ **Accueil** : Présentation des services, simulateur rapide, mise en avant du conventionné
- ✅ **Réservation** : Formulaire complet avec envoi via EmailJS
- ✅ **Simulateur de Prix** : Calcul précis avec API Google Maps Distance Matrix
- ✅ **Taxi Conventionné** : Explications CPAM, démarches, remboursement
- ✅ **Services** : Tous les services proposés (aéroports, longues distances, etc.)
- ✅ **À Propos** : Présentation du chauffeur, véhicule, certifications
- ✅ **Contact** : Formulaire de contact, coordonnées, FAQ
- ✅ **Mentions Légales** : Informations légales et politique RGPD

### Fonctionnalités Techniques
- 📱 **Design responsive** mobile-first
- 🚀 **Performance optimisée** (< 2 sec de chargement)
- 🔍 **SEO local** avec balises meta et Schema.org
- 📧 **EmailJS** pour l'envoi des formulaires
- 🗺️ **Google Maps API** pour le calcul de distance
- 💰 **Simulateur tarifaire** avec tarifs réglementaires
- ♿ **Accessibilité** conforme aux standards

## 🛠️ Technologies Utilisées

- **HTML5** : Structure sémantique
- **CSS3** : Styles modernes avec variables CSS
- **JavaScript Vanilla** : Pas de dépendance framework
- **EmailJS** : Service d'envoi d'emails
- **Google Maps Distance Matrix API** : Calcul de distance

## 📦 Installation

### Prérequis
- Un navigateur web moderne
- Un éditeur de code (VS Code recommandé)
- Un serveur web local ou hébergement web

### Installation Locale

1. **Télécharger les fichiers**
   ```bash
   # Si vous avez le projet en archive
   unzip taxi-julien-site.zip
   cd taxi-julien-site
   ```

2. **Ouvrir avec un serveur local**

   Option 1 - VS Code Live Server :
   - Installer l'extension "Live Server"
   - Clic droit sur `index.html` → "Open with Live Server"

   Option 2 - Python :
   ```bash
   python -m http.server 8000
   # Puis ouvrir http://localhost:8000
   ```

   Option 3 - Node.js :
   ```bash
   npx serve
   ```

## ⚙️ Configuration

### 1. Configuration EmailJS

Pour que les formulaires fonctionnent, configurez EmailJS :

#### A. Créer un compte EmailJS
1. Allez sur [https://www.emailjs.com/](https://www.emailjs.com/)
2. Créez un compte gratuit (200 emails/mois)

#### B. Configurer un service email
1. Dans le dashboard EmailJS, allez dans "Email Services"
2. Cliquez "Add New Service"
3. Choisissez votre fournisseur (Gmail, Outlook, etc.)
4. Suivez les instructions de configuration
5. Notez votre **Service ID**

#### C. Créer un template d'email
1. Allez dans "Email Templates"
2. Créez un nouveau template
3. Utilisez ces variables dans votre template :
   ```
   Pour la réservation :
   {{nom}}, {{prenom}}, {{telephone}}, {{email}}
   {{type_service}}, {{adresse_depart}}, {{adresse_arrivee}}
   {{date_course}}, {{heure_course}}
   {{nb_passagers}}, {{nb_bagages}}, {{commentaire}}

   Pour le contact :
   {{nom}}, {{prenom}}, {{email}}, {{telephone}}
   {{sujet}}, {{message}}
   ```
4. Notez votre **Template ID**

#### D. Obtenir votre clé publique
1. Allez dans "Account" → "General"
2. Copiez votre **Public Key**

#### E. Configurer les fichiers JS

Modifiez `js/reservation.js` :
```javascript
const EMAILJS_CONFIG = {
    serviceID: 'VOTRE_SERVICE_ID',      // Remplacer
    templateID: 'VOTRE_TEMPLATE_ID',    // Remplacer
    publicKey: 'VOTRE_PUBLIC_KEY'       // Remplacer
};
```

Modifiez `js/contact.js` avec les mêmes identifiants.

### 2. Configuration Google Maps API

Pour le simulateur de prix avec calcul de distance réel :

#### A. Créer une clé API Google Maps
1. Allez sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créez un nouveau projet (ou sélectionnez-en un)
3. Activez l'API "Distance Matrix API"
4. Créez des identifiants → Clé API
5. Copiez votre clé API

#### B. Sécuriser la clé (recommandé)
1. Dans Google Cloud Console → Identifiants
2. Cliquez sur votre clé API
3. Sous "Restrictions liées aux applications" :
   - Sélectionnez "Référents HTTP"
   - Ajoutez votre domaine : `votredomaine.com/*`
4. Sous "Restrictions liées aux API" :
   - Sélectionnez "Limiter la clé"
   - Choisissez "Distance Matrix API"

#### C. Configurer le fichier JS

Modifiez `js/simulateur.js` :
```javascript
const GOOGLE_MAPS_API_KEY = 'VOTRE_CLE_API_GOOGLE_MAPS';
```

### 3. Personnalisation des Coordonnées

Modifiez dans **TOUS les fichiers HTML** :

- `01 23 45 67 89` → Votre numéro de téléphone
- `contact@taxijulien.fr` → Votre email
- `+33123456789` → Votre numéro WhatsApp
- Liens vers vos réseaux sociaux si applicable

Fichiers concernés :
- `index.html`
- `reservation.html`
- `simulateur.html`
- `conventionné.html`
- `services.html`
- `a-propos.html`
- `contact.html`
- `mentions-legales.html`

### 4. Mentions Légales

Complétez `mentions-legales.html` avec :
- Votre nom complet
- SIRET
- Numéro de licence taxi
- Coordonnées de votre assurance
- Nom de votre hébergeur web
- Date de dernière mise à jour

## 📁 Structure du Projet

```
taxi-julien-site/
│
├── index.html                  # Page d'accueil
├── reservation.html            # Page de réservation
├── simulateur.html             # Simulateur de prix
├── conventionné.html           # Transport conventionné CPAM
├── services.html               # Tous les services
├── a-propos.html              # À propos
├── contact.html               # Contact
├── mentions-legales.html      # Mentions légales & RGPD
│
├── css/
│   └── style.css              # Styles principaux
│
├── js/
│   ├── main.js                # Scripts généraux
│   ├── reservation.js         # Logique réservation
│   ├── simulateur.js          # Logique simulateur
│   └── contact.js             # Logique contact
│
├── images/                    # Dossier pour vos images
│   └── (à ajouter)
│
└── README.md                  # Ce fichier
```

## 🎨 Personnalisation

### Couleurs

Modifiez les variables CSS dans `css/style.css` :

```css
:root {
  --primary-color: #1a3a5c;      /* Bleu nuit */
  --secondary-color: #d4af37;     /* Or */
  --accent-color: #2c5f8d;        /* Bleu clair */
  /* ... */
}
```

### Images

Ajoutez vos images dans le dossier `images/` :
- Logo du taxi
- Photo du véhicule
- Photo du chauffeur
- Favicon (16x16, 32x32, 64x64 px)

Puis mettez à jour les chemins dans les fichiers HTML.

### Tarifs

Les tarifs sont définis dans `js/simulateur.js` :

```javascript
const TARIFS = {
    minimum: 8.00,
    priseEnCharge: 2.35,
    tarifA: 1.11,  // Jour semaine
    tarifB: 1.44,  // Nuit semaine
    tarifC: 2.22,  // Jour weekend
    tarifD: 2.88,  // Nuit weekend
    attenteHeure: 34.60,
    heureDebutNuit: 19,
    heureFinNuit: 7,
    forfaits: {
        'aeroport_marseille_jour': 80.00,
        'aeroport_marseille_nuit': 100.00,
        // ...
    }
};
```

### Jours Fériés

Mettez à jour la liste des jours fériés chaque année dans `js/simulateur.js` :

```javascript
const JOURS_FERIES = [
    '2025-01-01', '2025-04-21', // ...
];
```

## 🚀 Déploiement

### Option 1 : Hébergement Classique (OVH, O2Switch, etc.)

1. Connectez-vous à votre hébergement via FTP (FileZilla)
2. Uploadez tous les fichiers à la racine (ou dans un sous-dossier)
3. Vérifiez que `index.html` est bien présent
4. Accédez à votre site via votre nom de domaine

### Option 2 : Netlify (Gratuit)

1. Créez un compte sur [Netlify](https://www.netlify.com/)
2. Glissez-déposez le dossier du projet
3. Votre site est en ligne instantanément !
4. Configuration du domaine personnalisé dans les réglages

### Option 3 : GitHub Pages (Gratuit)

1. Créez un repository GitHub
2. Uploadez tous les fichiers
3. Allez dans Settings → Pages
4. Sélectionnez la branche `main` comme source
5. Votre site sera accessible sur `votre-nom.github.io/repo-name`

### Option 4 : Vercel (Gratuit)

1. Créez un compte sur [Vercel](https://vercel.com/)
2. Importez votre projet depuis GitHub ou uploadez-le
3. Déploiement automatique en quelques secondes

## 📱 SEO & Référencement

### Optimisations Incluses

- ✅ Balises meta title et description sur toutes les pages
- ✅ Structure HTML sémantique (h1, h2, h3)
- ✅ URLs propres et descriptives
- ✅ Sitemap recommandé (à créer)
- ✅ Alt text sur les images (à compléter)
- ✅ Schema.org LocalBusiness (à ajouter)

### À Faire Après Déploiement

1. **Google My Business** : Créez/optimisez votre fiche
2. **Google Search Console** : Ajoutez votre site
3. **Sitemap.xml** : Générez et soumettez un sitemap
4. **Google Analytics** : Ajoutez le code de tracking (optionnel)
5. **Backlinks** : Inscrivez-vous sur des annuaires locaux

## 🐛 Dépannage

### Les formulaires ne s'envoient pas
- Vérifiez la configuration EmailJS dans `js/reservation.js` et `js/contact.js`
- Ouvrez la console du navigateur (F12) pour voir les erreurs
- Vérifiez que les IDs EmailJS sont corrects

### Le simulateur ne calcule pas les prix
- Vérifiez que la clé Google Maps API est bien configurée dans `js/simulateur.js`
- Vérifiez que l'API Distance Matrix est activée dans Google Cloud
- En mode dégradé, le simulateur utilise des estimations

### Le site ne s'affiche pas correctement
- Videz le cache du navigateur (Ctrl + F5)
- Vérifiez que tous les fichiers CSS et JS sont bien uploadés
- Vérifiez les chemins relatifs des fichiers

## 📞 Support

Pour toute question ou assistance :
- **Email** : contact@taxijulien.fr
- **Téléphone** : 01 23 45 67 89

## 📄 Licence

© 2024 Taxi Julien - Tous droits réservés

---

**Développé avec ❤️ pour Taxi Julien**
