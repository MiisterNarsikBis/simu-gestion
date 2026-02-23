# Modèle de données (MVP "solide gestion")

## Compte & société

### User
- Identifiant utilisateur
- Authentification

### Company
- `name` : Nom de l'entreprise
- `createdAt` : Date de création
- `ownerUserId` : Propriétaire de l'entreprise
- (plus tard : multi-company par user)

## État de jeu (important)

### GameState
- `companyId` : Référence à la société
- `sim_day` (int) : Compteur de jours simulés
- `last_midnight_processed_at` (datetime) : Pour la recharge quotidienne
- `days_available` (int) : Jours disponibles (quotas)
- `days_consumed_today` (int) : Optionnel si tu veux tracer
- `additional_days` (int) : Jours additionnels
- `last_recharge_date` (date) : Dernière date de recharge
- `timezone` (string) : "Europe/Paris"
- `global_quality_rating` (0–100) : Note de qualité globale
- `global_satisfaction` (0–100) : Satisfaction globale

## Finance

### FinanceState
- `companyId` : Référence à la société
- `cash_available` : Trésorerie dispo
- `share_capital` : Capital social
- `monthly_rent` : Loyer mensuel
- `daily_electricity_cost` : Coût électricité journalier
- `tax_rate` : Taux de taxe (simplifiée)

### LedgerEntry
- `companyId` : Référence à la société
- `sim_day` : Jour simulé
- `type` : INCOME/EXPENSE
- `category` : SALARY, RENT, ELECTRICITY, TAX, CLIENT_PAYMENT, LOAN_PAYMENT, TRAINING_COST, etc.
- `amount` : Montant
- `label` : Libellé

## Crédit

### Loan
- `companyId` : Référence à la société
- `principal` : Montant emprunté
- `annual_rate` : Taux annuel (= 0.04)
- `duration_months` : Durée en mois
- `start_sim_day` : Jour de début du crédit
- `remaining_principal` : Principal restant
- `monthly_payment` : Mensualité
- `status` : ACTIVE/PAID

**Règle plafond** :
- `max_loan_principal = 10 * share_capital`

## RH

### Employee
- `companyId` : Référence à la société
- `name` : Nom de l'employé
- `role` : MANAGER, RH, DEV, DESIGNER, GRAPHISTE, INTEGRATEUR
- `training_stars` (1–5) : Niveau de formation
- `availability_status` : DISPO, ARRET, FORMATION, SUR_POSTE
- `salary_daily` : Salaire journalier (ou monthly converti en daily)
- `skill_multiplier` : Multiplicateur de compétence (dérivé des étoiles, voir formule)

### Training
- `employeeId` : Référence à l'employé
- `target_stars` : Étoiles cibles
- `days_total` : Nombre total de jours de formation
- `days_remaining` : Jours restants
- `cost` : Coût de la formation
- `status` : ACTIVE/DONE

**Note** : Formation = salaire ↑ + "meilleur en tout". Donc on garde simple : étoiles → multiplicateur global.

## Clients & projets

### Client
- `companyId` : Référence à la société
- `name` : Nom du client
- `satisfaction` (0–100) : Niveau de satisfaction

### Project
- `companyId` : Référence à la société
- `clientId` : Référence au client
- `type` : VITRINE, ECOMMERCE, LANDING…
- `budget` : Budget du projet
- `deadline_sim_day` : Date limite (optionnel MVP)
- `status` : NEW, IN_PROGRESS, DONE, FAILED
- `pipeline_stage` : BRIEF, WIREFRAME, DESIGN, GRAPHISM, INTEGRATION, DEV, QA, DELIVERY
- `stage_progress` (0–100) : Progression dans l'étape
- `quality` (0–100) : Qualité du projet

### ProjectAssignment
- `projectId` : Référence au projet
- `employeeId` : Référence à l'employé
- `stage` : Sur quelle étape il bosse
- `allocation` : % ou juste bool "assigned" MVP
