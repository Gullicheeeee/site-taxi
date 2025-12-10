# 📁 Dossier Images

## Images à Ajouter

Pour compléter le site, ajoutez les images suivantes dans ce dossier :

### 1. Favicon
- **Nom** : `favicon.png`
- **Taille** : 64x64 pixels minimum (idéalement 512x512 pour compatibilité)
- **Format** : PNG avec fond transparent
- **Contenu** : Logo du taxi ou icône représentative

### 2. Logo du Taxi (optionnel)
- **Nom** : `logo.png`
- **Taille** : 200x200 pixels minimum
- **Format** : PNG avec fond transparent
- **Utilisation** : Header du site

### 3. Photo du Véhicule
- **Nom** : `vehicule.jpg`
- **Taille** : 1200x800 pixels
- **Format** : JPG haute qualité
- **Utilisation** : Page "À propos"

### 4. Photo du Chauffeur (optionnel)
- **Nom** : `chauffeur.jpg`
- **Taille** : 500x500 pixels
- **Format** : JPG
- **Utilisation** : Page "À propos"

### 5. Image de Fond Hero (optionnel)
- **Nom** : `hero-bg.jpg`
- **Taille** : 1920x600 pixels
- **Format** : JPG optimisé
- **Utilisation** : Arrière-plan de la section hero

## Optimisation des Images

Avant d'ajouter vos images, optimisez-les avec :
- [TinyPNG](https://tinypng.com/) pour réduire la taille
- [Squoosh](https://squoosh.app/) pour compression avancée

## Mise à Jour du HTML

Après avoir ajouté les images, mettez à jour les références dans les fichiers HTML :

```html
<!-- Favicon dans le <head> de chaque page -->
<link rel="icon" type="image/png" href="images/favicon.png">

<!-- Logo dans le header -->
<img src="images/logo.png" alt="Logo Taxi Julien">

<!-- Photo du véhicule -->
<img src="images/vehicule.jpg" alt="Taxi Julien - Véhicule">

<!-- Photo du chauffeur -->
<img src="images/chauffeur.jpg" alt="Julien - Chauffeur professionnel">
```

---

**Note** : Le site fonctionne parfaitement sans ces images grâce aux icônes emoji utilisées. Les images sont un plus pour la professionnalisation du site.
