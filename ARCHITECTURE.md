# Architecture - Stack & UI

## Stack technique

### Backend
- **Symfony** : Framework PHP pour la logique métier et les API
- **Twig** : Moteur de template pour le rendu côté serveur

### Frontend
- **Tailwind CSS** : Framework CSS utility-first
- **Style shadcn** : Design system inspiré de shadcn/ui
- **Stimulus** : Framework JavaScript minimaliste pour les interactions

## Structure des composants UI

Comme shadcn/ui est orienté React, on fait :

- **Tailwind config + tokens** : radius, border, muted, primary…
- **Composants Twig inspirés shadcn** : 
  - `components/ui/button.html.twig`
  - `components/ui/card.html.twig`
  - `components/ui/badge.html.twig`
  - `components/ui/dialog.html.twig`
  - `components/ui/table.html.twig`
  - `components/ui/tabs.html.twig`
  - `components/ui/toast.html.twig`
- **JS (Stimulus)** pour interactions : dialog, dropdown, toast

## Build frontend

### Tailwind CSS

- Build via Webpack Encore ou process Symfony
- `npm run dev` : compilation en mode développement
- `npm run build` : build production

Symfony intègre les assets buildés.
