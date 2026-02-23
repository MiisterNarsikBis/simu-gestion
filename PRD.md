# PRD - Product Requirements Document

## Règle centrale : système de jours (ton "carburant")

### Concepts

Le jeu n'avance que quand le joueur clique "Passer 1 jour".

Le joueur dispose d'un stock de "jours disponibles" (quotas) + des jours additionnels.

Passer un jour consomme 1 jour disponible, sinon consomme 1 jour additionnel si le quota est épuisé.

Chaque "jour simulé" déclenche :

- coûts (salaires, loyers, électricité, taxes…)
- progression projets via pipeline
- satisfaction / qualité
- facturation / encaissements (selon règles)
- mensualité de crédit (et intérêts)

### Règle "recharge" à 00h00 (réel, côté serveur)

Tous les jours à 00h00 (heure serveur / Europe/Paris) :

- si tous les jours disponibles de la veille ont été utilisés → +40 jours disponibles
- sinon → +30 jours disponibles

### Chaque dimanche

Si des jours disponibles ne sont toujours pas utilisés, alors jours disponibles = 0 (reset du stock non consommé)

**Traduction gameplay** : tu encourages la dépense régulière, sinon ça "pourrit".

### Jours additionnels

Stock séparé `additional_days`.

Utilisation automatique quand `days_available == 0`.

Sources possibles (MVP) :

- récompenses (niveau, objectifs, events)
- achats in-game (plus tard)
- leaderboard/multi (plus tard)

### Anti-triche / cohérence

Tout est piloté serveur :

- recharge à 00h00 + règle du dimanche
- consommation lors d'un "Passer 1 jour"

L'UI ne fait qu'appeler un endpoint : `POST /game/tick`
