# Feuille de route

## Phase 1 — Simulateur de crédit (0.0.1)
- [x] Simulateur, comparateur, remboursement anticipé, capacité d'emprunt
- [x] Quotas journaliers, historique, exports PDF / Word / Excel, partage par lien
- [x] README propre au projet, licence MIT

## Phase 2 — Connexion via Portier (0.1.0)
- [x] Client OpenID Connect sans dépendance (code + PKCE S256, state, nonce, RS256 par JWKS)
- [x] Routes `/auth/portier` et `/auth/portier/retour`, échange `/api/auth/portier/echange`
- [x] Compte local trouvé par e-mail vérifié, jamais de création ; 404 si désactivé
- [x] Bouton « Se connecter via Portier » sur `/login` (si `PORTIER_ACTIF=true`)
- [x] Tests avec un faux fournisseur (`tests/Feature/PortierTest.php`)
- [x] Documentation (`docs/portier.md`, README, `.env.example`)
- [ ] Déclarer le client `loan` dans Portier et remplir `.env` en production (Charles)
