# Design — « Loan » : simulateur de prêts (validé le 2026-07-07)

## Vue d'ensemble

Application freemium de simulation de prêts en **FCFA (XOF)**, construite sur le template
Maravel existant (Laravel 12 + Vue 3 + Vuetify + Sanctum).

### Quotas (validés)
| Profil | Quota simulations |
|---|---|
| Anonyme (suivi par IP) | 2 / jour |
| Connecté gratuit | 5 / jour |
| Premium — 1 500 FCFA/mois | Illimité |

Le paiement Premium n'est **pas implémenté** : le bouton « Passer Premium » ouvre une
modal « Bientôt disponible » ; le flag `is_premium` existe en base pour activation manuelle.

## Backend

- **`App\Services\LoanCalculator`** : source de vérité unique des calculs. 3 méthodes :
  - Annuités constantes (mensualité fixe)
  - Amortissement constant (capital fixe)
  - In fine (intérêts seuls, capital à la fin)
  - Assurance optionnelle (taux annuel sur capital initial).
- **Modèle `Simulation`** : `user_id` (nullable), `type` (`amortization`, `comparison`,
  `early_repayment`, `borrowing_capacity`), `params` JSON, `results` JSON, `share_token`,
  `ip_address`, timestamps.
- **`App\Services\QuotaService`** : compte les simulations du jour par IP (anonyme) ou
  `user_id` (connecté) ; 429 + message clair si quota atteint ; illimité si `is_premium`.
- **Exports** (connectés uniquement) : PDF (`barryvdh/laravel-dompdf`), Word
  (`phpoffice/phpword`), Excel (`phpoffice/phpspreadsheet`).
- **Partage** : lien public `/s/{share_token}` en lecture seule.

### API
- `POST /api/simulations` — calcule + enregistre (quota appliqué), accessible anonyme
- `GET /api/simulations/quota` — état du quota courant
- `GET /api/simulations` — historique (auth)
- `GET /api/simulations/{id}` — détail (auth, propriétaire)
- `DELETE /api/simulations/{id}` — suppression (auth, propriétaire)
- `GET /api/simulations/{id}/export/{format}` — pdf|word|excel (auth, propriétaire)
- `POST /api/simulations/{id}/share` — génère le lien public (auth)
- `GET /api/shared/{token}` — lecture publique d'une simulation partagée

## Frontend

- **Page d'accueil** (refonte, accès public) : hero avec mini-simulateur interactif à
  résultat instantané, sections fonctionnalités, « comment ça marche », tarifs
  (Gratuit / Premium 1 500 FCFA/mois), FAQ, footer.
- **`/simulateur`** : formulaire complet (montant, taux, durée, méthode, date de début,
  assurance) → cartes résumé, graphiques ApexCharts (donut capital/intérêts, courbe du
  solde restant dû), tableau d'amortissement complet, exports, partage.
- **`/mes-simulations`** (auth) : historique avec recherche/tri (`useClientTable`),
  revoir / re-télécharger / supprimer.
- **Bonus** :
  - `/comparateur` — 2-3 scénarios côte à côte avec graphiques
  - `/remboursement-anticipe` — impact d'un remboursement partiel/total (réduction de
    durée OU de mensualité, économies d'intérêts)
  - `/capacite-emprunt` — capacité d'emprunt avec taux d'endettement max **40 %**

## Ordre de réalisation

1. **Phase 1** : page d'accueil attrayante + simulateur d'amortissement opérationnel
   (migrations, calculs, quotas, affichage complet)
2. **Phase 2** : exports PDF / Word / Excel
3. **Phase 3** : historique + partage par lien
4. **Phase 4** : comparateur, remboursement anticipé, capacité d'emprunt, page Premium

## Formules

Taux mensuel `i = taux_annuel / 12 / 100`, capital `C`, durée `n` mois.

- **Annuités constantes** : mensualité `M = C · i / (1 − (1+i)^−n)` (si `i = 0`, `M = C/n`).
  Chaque mois : intérêts = solde × i ; capital = M − intérêts.
- **Amortissement constant** : capital mensuel `A = C / n` ; intérêts = solde × i ;
  mensualité décroissante.
- **In fine** : intérêts mensuels = C × i ; capital remboursé en une fois au dernier mois.
- **Assurance** : prime mensuelle = C × (taux_assurance / 12 / 100), constante, ajoutée à
  chaque échéance.
- **Capacité d'emprunt** : mensualité max = (revenus − charges) × 40 % ; capital
  empruntable = M · (1 − (1+i)^−n) / i.
