# Plan d'implémentation (tickets Cursor-friendly)

## Sprint 0 — Setup

### Objectifs
- Initialiser l'environnement de développement
- Configurer les outils de build
- Créer la structure de base

### Tâches
- [ ] Init Symfony + DB + Security
- [ ] Configuration Tailwind CSS :
  - [ ] Installer Tailwind CSS via npm
  - [ ] Configurer `tailwind.config.js` avec tokens shadcn (radius, colors, border, muted, primary)
  - [ ] Créer `assets/app.css` avec directives Tailwind
  - [ ] Configurer build via Webpack Encore ou build process Symfony
  - [ ] Configurer `package.json` scripts (dev/build)
- [ ] Composants UI style shadcn (Twig) :
  - [ ] Créer structure `templates/components/ui/`
  - [ ] Implémenter composants : Button, Card, Badge, Table, Dialog, Tabs, Toast
  - [ ] Adapter les classes Tailwind pour correspondre au style shadcn
  - [ ] Ajouter Stimulus controllers pour interactions (dialog, dropdown, toast)
- [ ] Base layout Twig :
  - [ ] Layout principal avec navigation
  - [ ] Intégration des assets via `asset()` Symfony
  - [ ] Structure responsive avec Tailwind
- [ ] Dashboard vide (page d'accueil basique)

## Sprint 1 — GameState + système de jours (règle critique)

### Objectifs
- Implémenter le système de jours (carburant du jeu)
- Gérer la recharge automatique
- Créer l'endpoint de tick

### Tâches
- [ ] Entités : Company, GameState
- [ ] Command/Service "MidnightRechargeService"
- [ ] Endpoint POST /game/tick
- [ ] UI : widget "Jours disponibles / additionnels", bouton "Passer 1 jour"
- [ ] Règles (commandes Symfony) :
  - [ ] Commande `app:game:recharge-days` :
    - [ ] Vérifier si minuit (00h00) est passé depuis `last_midnight_processed_at`
    - [ ] Si tous les jours disponibles de la veille ont été utilisés → +40 jours disponibles
    - [ ] Sinon → +30 jours disponibles
    - [ ] Mettre à jour `last_midnight_processed_at`
  - [ ] Commande `app:game:reset-sunday` :
    - [ ] Vérifier si c'est dimanche
    - [ ] Si des jours disponibles ne sont toujours pas utilisés → `days_available = 0`
  - [ ] Configurer cron jobs pour exécution automatique :
    - [ ] `app:game:recharge-days` à 00h00 chaque jour
    - [ ] `app:game:reset-sunday` le dimanche à 00h00

## Sprint 2 — Finance de base

### Objectifs
- Implémenter le système financier de base
- Gérer les coûts journaliers
- Afficher la trésorerie et le journal

### Tâches
- [ ] FinanceState + LedgerEntry
- [ ] Débit journalier salaires/loyer/électricité/taxe
- [ ] UI Finance : trésorerie + journal

## Sprint 3 — RH + formation + disponibilité

### Objectifs
- Gérer les employés et leurs statuts
- Implémenter le système de formation
- Calculer l'impact des étoiles sur performance

### Tâches
- [ ] Employee + Training
- [ ] UI employés : liste, statut, assignation simple
- [ ] Feature formation : démarrer / terminer
- [ ] Impact étoiles sur performance + salaire

## Sprint 4 — Clients + projets + pipeline

### Objectifs
- Créer le système de clients et projets
- Implémenter le pipeline de progression
- Gérer les assignations et livraisons

### Tâches
- [ ] Client, Project, ProjectAssignment
- [ ] Génération aléatoire de clients/projets (seed simple)
- [ ] UI projets : pipeline, progression, assignations
- [ ] TickEngine : progression + livraison + revenus

## Sprint 5 — Capital social + crédit

### Objectifs
- Implémenter le système de capital social
- Créer le système de crédit avec plafond
- Gérer les mensualités

### Tâches
- [ ] Share capital : action "augmenter capital" (débit cash → augmente capital)
- [ ] Crédit : plafond 10× capital, création prêt, mensualité, ledger
- [ ] UI finance : module crédit + échéancier simplifié

## Sprint 6 — Terrain multi / leaderboard (préparation)

### Objectifs
- Préparer l'infrastructure pour le multi-joueur
- Créer le système de scoring
- Implémenter le leaderboard de base

### Tâches
- [ ] Ajout tables "ScoreSnapshot" (companyId, date, score)
- [ ] Endpoint read-only leaderboard (plus tard : auth / anti-cheat)
