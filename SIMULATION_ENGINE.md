# Moteur de simulation : ce qui se passe quand on "Passe 1 jour"

## A) Consommation du "jour disponible"

Au début du tick :

1. Vérifier recharge à 00h00 (si nécessaire, voir section "Recharge")
2. Si `days_available > 0` → `days_available -= 1`
3. Sinon si `additional_days > 0` → `additional_days -= 1`
4. Sinon → erreur "Plus de jours"

## B) Coûts journaliers

### Salaires
Somme `employee.salary_daily` pour tous les employés actifs.

### Loyer
`monthly_rent / 30` (simple)

### Électricité
`daily_electricity_cost`

### Taxes
Modèle simple (MVP) :

- soit une taxe fixe journalière
- soit `tax_rate` appliquée sur revenus encaissés du jour (plus lisible)

Chaque coût → `LedgerEntry` négatif + baisse `cash`.

## C) Progression projets (pipeline "1 étape = 1 rôle dispo")

### Règle
Une étape avance seulement si :

- l'étape nécessite un rôle (ex DESIGN → DESIGNER)
- au moins 1 employé de ce rôle est DISPO et assigné au projet (ou auto-assign MVP)

### Progression journalière (simple et efficace)
```
progress_gain = base_gain * employee.skill_multiplier
```

où `employee.skill_multiplier` dépend des étoiles.

### Proposition multiplicateur
- étoiles 1 → 1.0
- étoiles 2 → 1.35
- étoiles 3 → 1.70
- étoiles 4 → 2.05
- étoiles 5 → 2.40

### Passage d'étape
Quand `stage_progress >= 100` :

- passer à l'étape suivante, `stage_progress = 0`
- si dernière étape terminée → `status = DONE`, déclenche livraison.

## D) Qualité & satisfaction (simple)

### Qualité
Qualité augmente en fonction du niveau des employés impliqués sur les étapes clés :

```
quality += quality_gain par jour de travail
quality_gain = base_quality_gain * employee.skill_multiplier
```

### Satisfaction client
- augmente si qualité finale > objectif (selon type de projet)
- diminue si retard (si tu actives deadline)

## E) Revenus

À la livraison :

- `cash += budget` (encaissement immédiat MVP)
- `ledger` income "CLIENT_PAYMENT"

Plus tard : paiement en plusieurs fois / délai.

## F) Formation

Si un employé est en FORMATION :

- `training.days_remaining -= 1`
- si 0 → `training_stars = target`, `salary_daily` augmente (règle simple)
- coût soit à l'inscription, soit étalé (MVP : au démarrage)

## G) Crédit (mensualité payée à chaque jour)

Tu veux : intérêt annuel 4% + "chaque jour paie une mensualité".

### MVP (simple, stable)
- `monthly_payment` calculé à la création du crédit (amortissement standard)

Chaque jour :

- si `sim_day % 30 == 0` alors débiter `monthly_payment`
- répartir en intérêts + principal (intérêts = `remaining_principal * annual_rate / 12`)

C'est "paiement mensuel", mais déclenché via le passage des jours simulés. C'est le plus compréhensible côté joueur.
