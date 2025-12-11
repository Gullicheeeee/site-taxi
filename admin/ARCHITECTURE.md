# Architecture Back-Office CMS SEO-First

## Vue d'ensemble

Ce back-office est conçu selon une approche **SEO-First**, permettant à un utilisateur non-technique de gérer le contenu tout en optimisant le référencement naturel. L'interface est inspirée de WordPress pour une prise en main intuitive.

---

## 🏗️ Structure des Modules

```
admin/
├── index.php              # Dashboard SEO avec score global
├── pages.php              # Liste des pages
├── page-edit.php          # Éditeur de page avancé
├── blog.php               # Gestion des articles (style WordPress)
├── blog-edit.php          # Éditeur d'article avec SEO, tags, programmation
├── categories.php         # Gestion des catégories et tags
├── media.php              # Médiathèque optimisée
├── seo-audit.php          # Audit SEO complet
├── redirections.php       # Gestion des redirections 301/302
├── sitemap.php            # Générateur sitemap & robots.txt
├── menus.php              # Gestion des menus (header/footer)
├── apparence.php          # Personnalisation (couleurs, polices, logo)
├── utilisateurs.php       # Gestion des utilisateurs et rôles
├── settings.php           # Paramètres du site
├── includes/
│   ├── header.php         # Navigation, recherche globale, notifications
│   └── footer.php         # Pied de page
├── assets/
│   └── style.css          # Styles du back-office
├── database/
│   └── schema_seo.sql     # Schéma BDD SEO complet
└── config.php             # Configuration Supabase
```

---

## 📊 Module 1 : Dashboard SEO

**Fichier:** `index.php`

### Fonctionnalités
- **Score SEO global** (0-100) avec jauge visuelle
- **Alertes SEO** en temps réel :
  - Pages sans meta title
  - Pages sans meta description
  - Images sans texte alternatif
  - Images trop lourdes
- **Statistiques rapides** : Pages, Articles, Images, Liens internes
- **Checklist SEO** : Validation des éléments essentiels
- **Actions rapides** : Accès direct aux fonctions clés

---

## 📄 Module 2 : Gestion des Pages

**Fichiers:** `pages.php`, `page-edit.php`

### Fonctionnalités
- Liste des pages avec score SEO individuel
- **Éditeur par blocs** :
  - Hero Section
  - Texte
  - Cartes/Grilles
  - FAQ
  - CTA
  - Image + Texte
  - Contact Info
- **SEO intégré par page** :
  - Titre SEO (avec compteur pixels)
  - Meta description (avec indicateur de longueur)
  - URL/Slug personnalisable
  - Mots-clés
- Prévisualisation Google
- Gestion des sections (ajout, suppression, réorganisation)

---

## 📝 Module 3 : Blog

**Fichiers:** `blog.php`, `blog-edit.php`

### Fonctionnalités
- Interface style WordPress
- Filtres : Tous / Publiés / Brouillons
- Actions au survol (Modifier, Publier, Aperçu, Supprimer)
- **Éditeur WYSIWYG** avec :
  - Formatage texte (gras, italique, souligné)
  - Titres H2, H3
  - Listes à puces
  - Liens
- **SEO de l'article** :
  - Meta title
  - Meta description
  - Slug
  - Catégorie
- Image à la une avec sélecteur média
- Prévisualisation complète

---

## 🖼️ Module 4 : Médiathèque

**Fichier:** `media.php`

### Fonctionnalités
- Upload d'images vers Supabase Storage
- Champs SEO obligatoires :
  - Texte alternatif (alt)
  - Nom de fichier
- Affichage en grille
- Copie d'URL en un clic
- Détection images trop lourdes
- Formats supportés : JPG, PNG, GIF, WebP

---

## 🔍 Module 5 : Audit SEO

**Fichier:** `seo-audit.php`

### Fonctionnalités
- **Score global** avec détail
- **Analyse automatique** :
  - Meta titles (présence, longueur)
  - Meta descriptions (présence, longueur)
  - Balises H1
  - Textes alternatifs images
  - Poids des images
  - Contenu des articles (longueur minimum)
- **Classification** :
  - Erreurs critiques (rouge)
  - Avertissements (orange)
  - Validés (vert)
- Liens directs vers la correction
- Recommandations contextuelles

---

## ↩️ Module 6 : Redirections

**Fichier:** `redirections.php`

### Fonctionnalités
- Ajout de redirections 301 (permanentes)
- Ajout de redirections 302 (temporaires)
- Liste des redirections actives
- Suppression facile
- Guide explicatif intégré

---

## 🗺️ Module 7 : Sitemap & Robots.txt

**Fichier:** `sitemap.php`

### Fonctionnalités
- **Génération automatique sitemap.xml** :
  - Toutes les pages
  - Tous les articles publiés
  - Dates de modification
  - Priorités configurables
- **Génération robots.txt** :
  - Configuration User-agent
  - Exclusion du back-office
  - Lien vers sitemap
- Aperçu avant génération
- Conseils SEO technique

---

## 🗄️ Schéma Base de Données

**Fichier:** `database/schema_seo.sql`

### Tables principales

```sql
-- Pages avec champs SEO étendus
pages (
  id, slug, title, status,
  hero_title, hero_subtitle, hero_image,
  meta_title, meta_description, meta_keywords,
  focus_keyword, secondary_keywords,
  seo_score, readability_score, word_count,
  is_indexed, noindex, nofollow, canonical_url
)

-- Sections/Blocs de contenu
page_sections (
  id, page_id, section_key, section_type,
  title, content, image, display_order,
  is_visible, seo_data, internal_links
)

-- Articles de blog
blog_posts (
  id, slug, title, excerpt, content,
  featured_image, category,
  meta_title, meta_description, meta_keywords,
  is_published, published_at
)

-- Médiathèque
media (
  id, filename, original_name, file_path, file_url,
  file_type, file_size, alt_text, title, caption,
  dimensions, is_optimized
)

-- Redirections
redirections (
  id, source_url, target_url, redirect_type,
  is_active, hit_count
)

-- Métriques SEO
seo_metrics (
  id, page_id, date,
  impressions, clicks, ctr, avg_position
)

-- Alertes SEO
seo_alerts (
  id, page_id, alert_type, severity,
  message, is_resolved
)

-- Journal d'activité
activity_log (
  id, user_id, action, entity_type,
  entity_id, changes, created_at
)
```

---

## 🎨 Interface Utilisateur

### Principes
- **Clarté** : Interface épurée, sans jargon technique
- **Efficacité** : Actions en 1-2 clics maximum
- **Feedback** : Alertes visuelles claires
- **Responsive** : Adapté desktop et tablette

### Couleurs
- Primaire : #3b82f6 (bleu)
- Succès : #22c55e (vert)
- Avertissement : #f59e0b (orange)
- Erreur : #ef4444 (rouge)
- Gris : #6b7280

### Composants
- Cards avec bordures douces
- Boutons avec états (hover, active)
- Badges de statut colorés
- Modales pour les aperçus
- Tables avec actions au survol

---

## 🔐 Sécurité

- Authentification par session PHP
- Échappement des données (fonction `e()`)
- Requêtes API Supabase avec clé sécurisée
- Protection CSRF (à ajouter)
- Validation des uploads (type MIME, taille)

---

## 🚀 Installation

1. Exécuter `database/schema_seo.sql` dans Supabase SQL Editor
2. Exécuter `supabase_data.sql` pour les données initiales
3. Configurer `config.php` avec les credentials Supabase
4. Accéder à `/admin/login.php`

---

## ☰ Module 8 : Gestion des Menus

**Fichier:** `menus.php`

### Fonctionnalités
- Gestion du menu principal (header)
- Gestion du menu footer
- Ajout de pages existantes en 1 clic
- Liens personnalisés
- Drag & drop pour réorganiser
- Ouverture dans nouvel onglet (optionnel)

---

## 🏷️ Module 9 : Catégories & Tags

**Fichier:** `categories.php`

### Fonctionnalités
- Création de catégories pour les articles
- Création de tags
- Couleurs personnalisées
- Slugs automatiques ou personnalisés
- Compteur d'articles par catégorie

---

## 👥 Module 10 : Utilisateurs

**Fichier:** `utilisateurs.php`

### Fonctionnalités
- Liste des utilisateurs avec statistiques
- Création de nouveaux utilisateurs
- 4 rôles : Administrateur, Éditeur, Auteur, Contributeur
- Modification des rôles
- Suppression (protection du dernier admin)
- Suivi des connexions

---

## 🎨 Module 11 : Personnalisation

**Fichier:** `apparence.php`

### Fonctionnalités
- **Couleurs** : Principale, secondaire, accent, texte, footer
- **Typographie** : 7 polices Google Fonts, taille de base
- **Header** : Style (transparent, solide, sticky)
- **Logo & Favicon** : URLs personnalisables
- **CTA** : Texte et numéro du bouton d'appel
- **Réseaux sociaux** : Affichage header/footer

---

## ⚙️ Module 12 : Réglages

**Fichier:** `settings.php`

### Sections
- **Général** : Nom, slogan, URL, format de date, mode maintenance
- **Contact** : Email, téléphone, WhatsApp, adresse
- **Réseaux sociaux** : Facebook, Instagram, Twitter, LinkedIn
- **Analytics** : Google Analytics, GTM, Facebook Pixel
- **Scripts** : Header et footer personnalisés
- **Sécurité** : Changement de mot de passe

---

## 🔍 Interface Globale

**Fichier:** `includes/header.php`

### Fonctionnalités
- **Recherche globale** : Pages et articles en temps réel
- **Notifications** : Alertes SEO (meta title/description manquants)
- **Navigation** : Sidebar responsive avec sections
- **Mobile** : Toggle sidebar sur petit écran

---

## 📈 Évolutions futures

- [x] Multi-utilisateurs avec rôles
- [ ] Analyse sémantique en temps réel
- [ ] Suggestions de mots-clés IA
- [ ] Intégration Google Search Console
- [ ] A/B testing de titres
- [ ] Historique des versions
- [ ] Workflow de validation
- [ ] Notifications par email
- [ ] Export PDF des rapports SEO
- [ ] Commentaires sur les articles
- [ ] Drag & drop dans l'éditeur de blocs
