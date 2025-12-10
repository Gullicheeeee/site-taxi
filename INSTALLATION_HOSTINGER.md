# 🚀 Installation sur Hostinger - Guide Complet

## Étape 1 : Créer la base de données MySQL

1. **Connectez-vous à Hostinger** (hpanel.hostinger.com)

2. **Accédez aux bases de données** :
   - Dans le panneau de contrôle, cherchez **"Bases de données MySQL"**
   - Cliquez sur **"Gérer"**

3. **Créez une nouvelle base de données** :
   - Cliquez sur **"Créer une nouvelle base de données"**
   - Nom de la base : `u123456789_taxi` (Hostinger ajoute automatiquement un préfixe)
   - Notez bien :
     - **Nom de la base** : u123456789_taxi
     - **Nom d'utilisateur** : u123456789_admin
     - **Mot de passe** : (celui que vous définissez)
     - **Nom d'hôte** : localhost (généralement)

4. **Accédez à phpMyAdmin** :
   - Cliquez sur **"Gérer"** à côté de votre base de données
   - Vous serez connecté automatiquement à phpMyAdmin

5. **Importez le schéma** :
   - Cliquez sur l'onglet **"Importer"**
   - Cliquez sur **"Choisir un fichier"**
   - Sélectionnez le fichier `admin/database.sql`
   - Cliquez sur **"Exécuter"**
   - ✅ Vous devriez voir : "Importation réussie"

## Étape 2 : Configurer le fichier config.php

Avant d'uploader les fichiers, vous devez modifier `admin/config.php` avec vos identifiants Hostinger.

**Modifiez ces lignes dans admin/config.php :**

```php
// Configuration de la base de données
define('DB_HOST', 'localhost');  // Généralement 'localhost' chez Hostinger
define('DB_NAME', 'u123456789_taxi');  // Remplacez par votre nom de base
define('DB_USER', 'u123456789_admin');  // Remplacez par votre utilisateur
define('DB_PASS', 'votre_mot_de_passe');  // Remplacez par votre mot de passe
define('DB_CHARSET', 'utf8mb4');
```

## Étape 3 : Uploader les fichiers

### Option A : Via File Manager (Recommandé)

1. **Accédez au File Manager** :
   - Dans hPanel, cherchez **"Gestionnaire de fichiers"**
   - Cliquez sur **"Ouvrir"**

2. **Naviguez vers public_html** :
   - Double-cliquez sur le dossier `public_html`
   - C'est ici que vous devez mettre vos fichiers

3. **Supprimez les fichiers par défaut** (si nécessaire) :
   - Sélectionnez tous les fichiers (index.html, etc.)
   - Cliquez sur **"Supprimer"**

4. **Uploadez tous vos fichiers** :
   - Cliquez sur **"Upload"** en haut
   - Sélectionnez TOUS les fichiers et dossiers de "Site Taxi"
   - Attendez la fin de l'upload

**Structure finale dans public_html :**
```
public_html/
├── admin/
│   ├── assets/
│   ├── includes/
│   ├── config.php
│   ├── login.php
│   └── ...
├── css/
├── js/
├── images/
├── index.html
├── blog.html
└── ...
```

### Option B : Via FTP (FileZilla)

1. **Téléchargez FileZilla** : https://filezilla-project.org/

2. **Récupérez vos identifiants FTP** :
   - Dans hPanel > Hébergement > **"Comptes FTP"**
   - Notez : Hôte, Nom d'utilisateur, Mot de passe, Port

3. **Connectez-vous via FileZilla** :
   - Hôte : ftp.votresite.com
   - Utilisateur : votre_user
   - Mot de passe : votre_pass
   - Port : 21

4. **Uploadez les fichiers** :
   - Côté gauche : vos fichiers locaux
   - Côté droit : serveur (allez dans public_html)
   - Glissez-déposez tous vos fichiers

## Étape 4 : Créer le dossier uploads

1. **Dans File Manager ou FTP** :
   - Créez un dossier `uploads` à la racine (dans public_html)
   - Définissez les permissions à **755** ou **775**

2. **Vérifier les permissions** :
   - Clic droit sur le dossier `uploads`
   - Sélectionnez **"Permissions"** ou **"Change permissions"**
   - Cochez : Read, Write, Execute pour Owner et Group

## Étape 5 : Tester l'installation

1. **Accédez à votre site** :
   - Site principal : `https://votredomaine.com`
   - Back office : `https://votredomaine.com/admin/login.php`

2. **Connectez-vous au back office** :
   - Username : `admin`
   - Password : `admin123`

3. **⚠️ CHANGEZ LE MOT DE PASSE immédiatement !**

## Étape 6 : Sécurité (Recommandé)

### Protéger le dossier admin avec .htaccess

Le fichier `.htaccess` a déjà été créé. Si vous voulez ajouter une protection par mot de passe supplémentaire :

1. **Via hPanel** :
   - Allez dans **"Protection de répertoire"**
   - Sélectionnez le dossier `admin`
   - Créez un utilisateur et mot de passe

2. **Changez le mot de passe admin** :
   - Connectez-vous au back office
   - Allez dans phpMyAdmin
   - Table `users` > Modifiez le champ `password`
   - Utilisez ce script pour générer un hash :
   ```php
   <?php echo password_hash('VOTRE_NOUVEAU_MOT_DE_PASSE', PASSWORD_DEFAULT); ?>
   ```

## 🔧 Dépannage

### Erreur "Impossible de se connecter à la base de données"

✅ **Vérifiez dans admin/config.php :**
- Le nom de la base est correct (avec le préfixe Hostinger)
- Le nom d'utilisateur est correct
- Le mot de passe est correct
- Le hostname est `localhost` (ou celui fourni par Hostinger)

### Erreur 500 - Internal Server Error

✅ **Causes possibles :**
- Erreur de syntaxe PHP
- Permissions incorrectes
- Fichier .htaccess mal configuré

**Solution :**
- Vérifiez les logs d'erreur dans hPanel > **"Logs d'erreur"**
- Vérifiez que PHP 7.4+ est activé

### Erreur d'upload d'images

✅ **Vérifiez :**
- Le dossier `uploads/` existe
- Les permissions sont 755 ou 775
- La taille max d'upload est suffisante (dans hPanel > Configuration PHP)

### Le CSS/JS ne se charge pas

✅ **Vérifiez :**
- Les chemins sont corrects (relatifs, pas absolus)
- Les fichiers ont bien été uploadés
- Vider le cache du navigateur (Ctrl+F5)

## 📝 Checklist finale

- [ ] Base de données créée et schéma importé
- [ ] Fichier config.php configuré avec les bons identifiants
- [ ] Tous les fichiers uploadés dans public_html
- [ ] Dossier uploads/ créé avec bonnes permissions
- [ ] Site accessible via votre domaine
- [ ] Back office accessible via /admin/login.php
- [ ] Connexion réussie avec admin/admin123
- [ ] Mot de passe admin changé
- [ ] Test de création d'article
- [ ] Test d'upload d'image

## 🎯 URLs importantes

- **Site principal** : https://votredomaine.com
- **Back office** : https://votredomaine.com/admin/login.php
- **phpMyAdmin** : Via hPanel > Bases de données > Gérer
- **File Manager** : Via hPanel > Gestionnaire de fichiers

## 📞 Support

Si vous rencontrez des problèmes :
1. Consultez les logs d'erreur dans hPanel
2. Vérifiez ce guide étape par étape
3. Contactez le support Hostinger (ils sont très réactifs)

---

**Bon déploiement ! 🚀**
