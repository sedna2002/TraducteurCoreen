# Vocabulaire coréen 🇰🇷

**Application Web d’apprentissage du vocabulaire coréen, à partir du français.**

Choisissez un thème, découvrez les mots et leur prononciation, puis entraînez-vous avec un quiz interactif. L’application fonctionne sur un serveur PHP avec MariaDB pour la gestion des comptes ; le vocabulaire est lu depuis un fichier CSV.

> **État du projet :** projet personnel en cours d’évolution. Certaines parties historiques du code nécessitent encore une modernisation de la sécurité avant une exposition publique sur Internet.

![Accueil de Vocabulaire coréen](docs/screenshots/accueil.png)

## Fonctionnalités

- **8 grandes catégories**, parmi lesquelles les animaux, l’alimentation, la maison, les relations humaines, le corps et la nature.
- **31 rubriques** et **544 entrées de vocabulaire** dans le fichier `dictionnaire.csv` fourni avec cette version.
- Sélection d’une ou plusieurs rubriques, avec compteur visuel.
- Parcours d’**apprentissage** : présentation de mots en coréen avec leur traduction française.
- **Quiz à quatre propositions** après l’étape de mémorisation, avec retour visuel sur les réponses.
- **Dictionnaire consultable** regroupant les mots par thème.
- **Prononciation de synthèse** via la Web Speech API du navigateur, sous réserve de la présence d’une voix coréenne compatible.
- Comptes utilisateurs, connexion, déconnexion et historique des connexions.
- Interface responsive pour ordinateur, tablette et téléphone.

### Page de connexion

![Page de connexion de Vocabulaire coréen](docs/screenshots/connexion.png)

## Technologies

| Composant | Utilisation |
| --- | --- |
| PHP | Pages, sessions et gestion des comptes |
| MariaDB / MySQL | Utilisateurs, paramètres et historique |
| JavaScript, HTML, CSS | Interface, sélection, dictionnaire et quiz |
| Web Speech API | Synthèse vocale dans le navigateur |
| CSV (`;`) | Source du vocabulaire français–coréen |
| jQuery 3.6.0 (fourni) | Bibliothèque JavaScript présente dans le projet |

Le serveur n’effectue pas la synthèse vocale : le rendu et les voix disponibles dépendent du navigateur et du système d’exploitation du visiteur.

## Installation

### Prérequis

- Serveur Web avec PHP 8.x et les extensions `pdo_mysql` (ou pilote PDO MySQL), avec sessions PHP actives.
- MariaDB ou MySQL, et un compte autorisé à créer la base / les tables lors de l’installation initiale.
- Navigateur récent avec JavaScript activé.
- Accès à un serveur Web : l’ouverture directe des fichiers en `file://` ne remplace pas une installation PHP.

L’application a notamment été utilisée sur **Ubuntu 26.04 / Raspberry Pi, Apache, PHP 8.5 et MariaDB 11.8**. Cela décrit l’environnement d’utilisation, et non une matrice de compatibilité certifiée.

### 1. Déployer les fichiers

Copiez le contenu de l’application, par exemple dans :

```text
/var/www/html/traducteurCoreenFrancais/
```

Le document root doit servir `index.php`, `Connexion.php`, les scripts et `dictionnaire.csv`.

### 2. Créer la base MariaDB (nouvelle installation uniquement)

Depuis un compte administrateur MariaDB, importez le fichier fourni :

```bash
sudo mariadb < initialisation_vocabulairecoreen.sql
```

Ce script crée la base `vocabulairecoreen` et les tables `utilisateurs`, `historique`, `parametrage` et `table_date_update`. **Si vous disposez déjà d’une base utilisée par le site, sauvegardez-la et ne la remplacez pas par une base vide.**

Créez ensuite un utilisateur SQL dédié (dans MariaDB, en remplaçant l’exemple de mot de passe) :

```sql
CREATE USER 'coreen_app'@'localhost' IDENTIFIED BY 'REMPLACER_PAR_UN_SECRET_FORT';
GRANT SELECT, INSERT, UPDATE, DELETE
ON vocabulairecoreen.* TO 'coreen_app'@'localhost';
```

### 3. Configurer l’accès MariaDB hors du dossier Web

La version sécurisée de `Methodes_Connexions.php` lit la configuration à l’emplacement **`/etc/coreen/database.php`**. Ne placez pas les secrets dans le dépôt GitHub ni dans le répertoire public.

```bash
sudo install -d -o root -g www-data -m 0750 /etc/coreen
sudo install -o root -g www-data -m 0640 \
  config-hors-web/database.php.example /etc/coreen/database.php
sudo nano /etc/coreen/database.php
```

Complétez localement les paramètres `host`, `database`, `username` et `password` ; ils doivent correspondre à votre serveur MariaDB. Le fichier `config-hors-web/database.php.example` est un **modèle sans secret**, à fournir avec la distribution.

Vérifiez l’accès pour le serveur Web :

```bash
sudo -u www-data test -r /etc/coreen/database.php && echo 'Configuration lisible'
php -l /etc/coreen/database.php
```

> **Important :** si vous partez d’une ancienne archive qui conserve des identifiants dans `Methodes_Connexions.php`, appliquez d’abord le correctif de configuration externe. N’installez pas le nouveau fichier PHP avant la création de `/etc/coreen/database.php`.

### 4. Créer et activer un compte applicatif

1. Ouvrez `CreationUser.php` et créez le compte utilisateur.
2. Un nouvel utilisateur peut nécessiter une validation administrative.
3. Pour une installation initiale, un administrateur MariaDB peut valider le compte concerné :

```sql
UPDATE vocabulairecoreen.utilisateurs
SET Valider = 1
WHERE Login = 'IDENTIFIANT_DU_COMPTE';
```

Connectez-vous ensuite avec `Connexion.php`, puis ouvrez `index.php`.

## Utilisation

1. Sélectionnez une catégorie complète ou plusieurs rubriques.
2. Cliquez sur **Commencer la révision** pour mémoriser les mots proposés.
3. Passez au quiz et choisissez la bonne traduction parmi les quatre réponses.
4. Utilisez **Dictionnaire** pour revoir le vocabulaire, ou **Catégories** pour modifier la sélection.

Les données lexicales proviennent principalement de `dictionnaire.csv`, dont les en-têtes attendus sont `theme;sous-theme;francais;coreen;image`. L’ajout de mots s’effectue en modifiant le CSV, en conservant l’encodage UTF-8 et le séparateur point-virgule.

## Structure du projet

```text
index.php / index.css / index.js        Accueil, thèmes et exercices
Connexion.php / connexion.css / connexion.js   Connexion
CreationUser.php / creation-user.*     Inscription
Connexion_Verification.php            Vérification de connexion
CreationUser_Verification.php        Enregistrement des comptes
Methodes_Connexions.php              Accès MariaDB et sessions
Deconnexion.php                       Déconnexion
dictionnaire.csv                      Vocabulaire chargé par l’interface
initialisation_vocabulairecoreen.sql Création initiale des tables
config-hors-web/database.php.example Modèle de configuration (sans secret)
```

## Sécurité et limites connues

- Les **mots de passe des comptes utilisent encore l’ancien mécanisme MD5**. Une migration vers `password_hash()` et `password_verify()` est nécessaire avant une mise en production publique.
- Certaines parties historiques, notamment `CreationUser_Verification.php`, utilisent encore des requêtes SQL construites par concaténation. **Un audit et l’emploi de requêtes préparées** restent nécessaires.
- Des fichiers hérités d’autres projets peuvent être présents dans les anciennes archives : revoyez le contenu du dépôt avant de le publier.
- Le CSV de vocabulaire étant un fichier statique, il peut être téléchargé directement si le serveur est accessible publiquement.
- Activez HTTPS et évitez d’exposer l’application à Internet tant que la sécurisation des comptes n’est pas terminée.
- N’ajoutez jamais `/etc/coreen/database.php`, des sauvegardes contenant des données privées ou de véritables clés API dans Git. Si un secret a déjà été publié, **révoquez-le et remplacez-le**.

## Publication et contributions

Le projet est évolutif : corrections, ajout de vocabulaire, améliorations pédagogiques et durcissement de la sécurité sont les principales pistes de travail. Pour signaler un problème, ouvrez une issue avec les étapes de reproduction, sans inclure d’identifiants ou d’informations personnelles.

**Licence :** aucune licence open source n’est définie dans les fichiers examinés. Ajoutez un fichier `LICENSE` adapté à vos intentions avant de présenter le projet comme libre ou réutilisable.
