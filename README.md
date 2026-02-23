# Game Test - Jeu de gestion d'entreprise

Jeu de simulation de gestion d'entreprise développé avec Symfony, Twig et Tailwind CSS.

## Stack technique

- **Backend** : Symfony 7.0
- **Frontend** : Twig + Tailwind CSS (style shadcn)
- **Base de données** : Doctrine ORM
- **Build** : Webpack Encore

## Installation

### Prérequis

- PHP 8.2+
- Composer
- Node.js et npm
- Symfony CLI (optionnel)

### Étapes

1. Installer les dépendances PHP :
```bash
composer install
```

2. Installer les dépendances Node.js :
```bash
npm install
```

3. Configurer la base de données dans `.env` :
```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/gametest?serverVersion=8.0"
```

4. Créer la base de données :
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Compiler les assets :
```bash
npm run dev
# ou en mode watch
npm run watch
```

6. Lancer le serveur de développement :
```bash
symfony server:start
# ou
php -S localhost:8000 -t public
```

## Structure du projet

- `src/` : Code source PHP (Controllers, Entities, Services)
- `templates/` : Templates Twig
  - `components/ui/` : Composants UI style shadcn
- `assets/` : Assets frontend (CSS, JS)
- `config/` : Configuration Symfony
- `public/` : Point d'entrée web

## Documentation

Voir les fichiers de documentation :
- `PRD.md` : Product Requirements Document
- `ARCHITECTURE.md` : Architecture technique
- `DATA_MODEL.md` : Modèle de données
- `SIMULATION_ENGINE.md` : Moteur de simulation
- `IMPLEMENTATION_PLAN.md` : Plan d'implémentation

## Développement

### Compiler les assets

```bash
# Mode développement
npm run dev

# Mode watch (recompilation automatique)
npm run watch

# Mode production
npm run build
```

### Commandes Symfony utiles

```bash
# Créer une entité
php bin/console make:entity

# Créer une migration
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate

# Créer un contrôleur
php bin/console make:controller
```

## Sprint 0 - Terminé ✅

- [x] Init Symfony + DB + Security
- [x] Configuration Tailwind CSS avec tokens shadcn
- [x] Composants UI style shadcn (Button, Card, Badge, Table)
- [x] Base layout Twig avec navigation
- [x] Dashboard vide

## Prochaines étapes

Voir `IMPLEMENTATION_PLAN.md` pour le plan complet des sprints suivants.
