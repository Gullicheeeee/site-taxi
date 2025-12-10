# Back-Office Taxi Julien 🚖

Système de gestion de contenu (CMS) pour le site Taxi Julien.

## 🚀 Installation

### 1. Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache, Nginx, ou PHP built-in server)
- Extension PHP PDO MySQL activée

### 2. Configuration de la base de données

1. Créez une base de données MySQL :
```sql
CREATE DATABASE taxi_julien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importez le schéma de base de données :
```bash
mysql -u root -p taxi_julien < admin/database.sql
```

3. Configurez les paramètres de connexion dans `admin/config.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'taxi_julien');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### 3. Configuration des permissions

Assurez-vous que le dossier `uploads/` est accessible en écriture :
```bash
chmod 755 uploads/
```

### 4. Accès au back-office

URL : `http://votre-site.com/admin/login.php`

**Identifiants par défaut :**
- Utilisateur : `admin`
- Mot de passe : `admin123`

⚠️ **IMPORTANT** : Changez immédiatement le mot de passe après la première connexion !

## 📚 Fonctionnalités

### Dashboard
- Vue d'ensemble des statistiques
- Accès rapide aux actions principales
- Liste des derniers articles

### Gestion du Blog
- **Liste des articles** : Visualiser, filtrer et rechercher tous les articles
- **Créer un article** : Rédiger de nouveaux articles avec éditeur HTML
- **Éditer un article** : Modifier le contenu, les métadonnées SEO
- **Publier/Dépublier** : Gérer le statut de publication
- **Supprimer** : Supprimer les articles non désirés

### Gestion des Pages
- Éditer les métadonnées SEO de chaque page
- Gérer les titres, descriptions et mots-clés
- Publier/Dépublier les pages

### Bibliothèque Médias
- Upload d'images (JPG, PNG, GIF, WebP)
- Taille maximum : 5 MB par fichier
- Copier l'URL des images en un clic
- Supprimer les médias non utilisés

### Paramètres du Site
- Informations générales (nom, téléphone, email, adresse)
- Réseaux sociaux
- Google Analytics
- Activation/Désactivation des fonctionnalités

## 🎨 Utilisation

### Créer un nouvel article de blog

1. Cliquez sur **"Blog"** dans la sidebar puis **"Nouvel article"**
2. Remplissez les informations :
   - **Titre** : Le titre principal de l'article
   - **Slug** : L'URL de l'article (généré automatiquement depuis le titre)
   - **Catégorie** : Voyages, Santé, Conseils, etc.
   - **Extrait** : Résumé court (300 caractères max)
   - **Contenu** : Le contenu HTML de l'article
   - **Image à la une** : Emoji ou URL d'image
3. Optimisez le SEO :
   - **Titre Meta** : Titre optimisé pour Google (50-60 caractères)
   - **Description Meta** : Description pour les résultats de recherche (150-160 caractères)
4. Cochez **"Publier"** si vous souhaitez publier immédiatement
5. Cliquez sur **"Enregistrer"**

### Éditer un article existant

1. Allez dans **"Blog"** > **"Articles"**
2. Cliquez sur **"Éditer"** ou directement sur la ligne de l'article
3. Modifiez le contenu souhaité
4. Cliquez sur **"Enregistrer les modifications"**

### Uploader une image

1. Allez dans **"Médias"** > **"Bibliothèque"**
2. Cliquez sur **"Choisir un fichier"**
3. Sélectionnez votre image (max 5 MB)
4. Cliquez sur **"Uploader"**
5. Une fois uploadée, cliquez sur **"📋 URL"** pour copier le chemin de l'image
6. Utilisez cette URL dans vos articles

### Gérer les métadonnées d'une page

1. Allez dans **"Pages"**
2. Cliquez sur **"Éditer"** pour la page souhaitée
3. Modifiez les champs SEO :
   - Titre Meta
   - Description Meta
   - Mots-clés
4. Cliquez sur **"Enregistrer"**

## 🔒 Sécurité

### Changer le mot de passe admin

Le mot de passe est hashé avec bcrypt. Pour le changer :

1. Connectez-vous à votre base de données
2. Exécutez cette requête (remplacez `NOUVEAU_MOT_DE_PASSE`) :
```sql
UPDATE users
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';
```

Ou utilisez ce script PHP pour générer un hash :
```php
<?php
echo password_hash('VOTRE_NOUVEAU_MOT_DE_PASSE', PASSWORD_DEFAULT);
?>
```

### Recommandations de sécurité

- ✅ Changez le mot de passe par défaut immédiatement
- ✅ Utilisez HTTPS sur votre site
- ✅ Sauvegardez régulièrement votre base de données
- ✅ Limitez l'accès au dossier `/admin` via .htaccess si possible
- ✅ Gardez PHP et MySQL à jour

## 🛠️ Structure des fichiers

```
admin/
├── assets/
│   ├── admin.css          # Styles du back-office
│   └── admin.js           # Scripts JavaScript
├── includes/
│   ├── header.php         # En-tête commun
│   └── footer.php         # Pied de page commun
├── config.php             # Configuration et fonctions
├── database.sql           # Schéma de base de données
├── login.php              # Page de connexion
├── logout.php             # Déconnexion
├── index.php              # Dashboard
├── blog.php               # Liste des articles
├── blog-new.php           # Créer un article
├── blog-edit.php          # Éditer un article
├── pages.php              # Liste des pages
├── page-edit.php          # Éditer une page
├── media.php              # Bibliothèque médias
└── settings.php           # Paramètres du site
```

## 📊 Base de données

### Tables principales

- **users** : Utilisateurs admin
- **blog_posts** : Articles du blog
- **pages** : Pages du site avec métadonnées SEO
- **page_sections** : Sections éditables des pages
- **media** : Bibliothèque d'images
- **settings** : Paramètres globaux du site
- **tarifs** : Tarifs pour le simulateur
- **reservations** : Demandes de réservation

## 🐛 Dépannage

### Erreur de connexion à la base de données

Vérifiez les informations de connexion dans `config.php` :
- Nom d'hôte (généralement `localhost`)
- Nom de la base de données
- Nom d'utilisateur
- Mot de passe

### Erreur d'upload d'images

1. Vérifiez que le dossier `uploads/` existe
2. Vérifiez les permissions : `chmod 755 uploads/`
3. Vérifiez la taille maximale d'upload dans `php.ini` :
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```

### Page blanche après connexion

1. Activez l'affichage des erreurs PHP
2. Vérifiez les logs d'erreur PHP
3. Assurez-vous que toutes les extensions PHP requises sont installées

## 📝 Support

Pour toute question ou problème :
1. Consultez ce README
2. Vérifiez les logs d'erreur
3. Contactez votre développeur

## 🎯 Prochaines étapes recommandées

- [ ] Changer le mot de passe par défaut
- [ ] Configurer les paramètres du site
- [ ] Importer les articles existants dans la base de données
- [ ] Configurer Google Analytics
- [ ] Tester toutes les fonctionnalités
- [ ] Faire une sauvegarde de la base de données

---

**Version** : 1.0
**Dernière mise à jour** : Novembre 2024
