# simu-gestion

Jeu de simulation de gestion d'entreprise développé avec Symfony, Twig et Tailwind CSS.

## 🎮 Description

Simulation de gestion d'entreprise où vous gérez une agence web : recrutement d'employés, gestion de projets clients, finances, formations, et plus encore.

## 🛠️ Technologies

- **Backend** : Symfony 7
- **Frontend** : Twig + Tailwind CSS + Stimulus
- **Base de données** : MySQL (via Doctrine ORM)
- **Build** : Webpack Encore

## 📋 Sprints

### Sprint 0 - Setup ✅
- Configuration Symfony
- Configuration base de données
- Configuration sécurité
- Configuration Tailwind CSS
- Composants UI de base
- Layout de base
- Dashboard vide

### Sprint 1 - GameState et système de jours ✅
- Entités GameState
- Service MidnightRechargeService
- Endpoint `/game/tick`
- Widget UI jours disponibles
- Commandes Symfony pour recharge/reset
- Onboarding (création d'entreprise)

### Sprint 2 - Finance de base ✅
- FinanceState
- LedgerEntry
- Coûts journaliers (loyer, électricité, salaires)
- UI finance

### Sprint 3 - HR, formation et disponibilité ✅
- Entités Employee et Training
- Multiplicateur de compétence
- Coûts salaires
- UI employés
- Fonctionnalités de formation
- Progression des formations dans TickEngine

### Sprint 4 - Clients, projets et pipeline ✅
- Entités Client, Project, ProjectAssignment
- Système de pipeline (8 étapes)
- Service de progression de projets
- Livraison et revenus
- Génération aléatoire de clients/projets
- UI projets avec pipeline et progression

### Sprint 5 - Capital social + crédit ✅
- Système de capital social
- Action "augmenter capital"
- Système de crédit avec plafond (10× capital)
- Gestion des mensualités
- UI finance complète

### Sprint 6 - Terrain multi / leaderboard (préparation)
- Préparation multi-joueurs
- Système de leaderboard

## 🚀 Installation

```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances Node
npm install

# Configurer la base de données dans .env
# Puis créer la base et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Compiler les assets
npm run build
# ou en mode dev
npm run dev
```

## 📝 Commandes utiles

```bash
# Recharger les jours disponibles (à exécuter quotidiennement)
php bin/console app:game:recharge-days

# Reset des jours non utilisés le dimanche
php bin/console app:game:reset-sunday
```

## 📚 Documentation

- `PRD.md` : Règles du jeu et mécaniques principales
- `ARCHITECTURE.md` : Architecture technique
- `DATA_MODEL.md` : Modèle de données
- `SIMULATION_ENGINE.md` : Moteur de simulation
- `IMPLEMENTATION_PLAN.md` : Plan d'implémentation détaillé
- `CRON.md` : Configuration des tâches cron
