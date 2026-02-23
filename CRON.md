# Configuration des tâches cron

Pour que le système de recharge des jours fonctionne automatiquement, vous devez configurer des tâches cron.

## Commandes disponibles

### `app:game:recharge-days`
Recharge les jours disponibles pour toutes les entreprises.
- **Quand** : Tous les jours à 00h00
- **Règle** : +40 jours si tous les jours de la veille ont été utilisés, sinon +30 jours

### `app:game:reset-sunday`
Reset les jours disponibles non utilisés chaque dimanche.
- **Quand** : Chaque dimanche à 00h00
- **Règle** : Si des jours disponibles ne sont toujours pas utilisés → `days_available = 0`

## Configuration cron (Linux/Mac)

Éditez votre crontab :
```bash
crontab -e
```

Ajoutez ces lignes :
```cron
# Recharge des jours à minuit chaque jour
0 0 * * * cd /chemin/vers/gametest && php bin/console app:game:recharge-days >> /var/log/gametest-recharge.log 2>&1

# Reset des jours le dimanche à minuit
0 0 * * 0 cd /chemin/vers/gametest && php bin/console app:game:reset-sunday >> /var/log/gametest-reset.log 2>&1
```

## Configuration avec Task Scheduler (Windows)

1. Ouvrez le Planificateur de tâches Windows
2. Créez une tâche de base
3. Configurez :
   - **Déclencheur** : Quotidien à 00h00
   - **Action** : Démarrer un programme
   - **Programme** : `php.exe`
   - **Arguments** : `bin/console app:game:recharge-days`
   - **Répertoire de départ** : Chemin vers votre projet

4. Répétez pour la commande `app:game:reset-sunday` avec un déclencheur hebdomadaire le dimanche

## Test manuel

Vous pouvez tester les commandes manuellement :

```bash
# Tester la recharge
php bin/console app:game:recharge-days

# Tester le reset (fonctionne seulement le dimanche)
php bin/console app:game:reset-sunday
```

## Notes importantes

- Les commandes utilisent le timezone `Europe/Paris` par défaut
- Assurez-vous que le serveur PHP a accès à la base de données
- Les logs sont recommandés pour le débogage
