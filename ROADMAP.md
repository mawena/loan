# Feuille de route

## Phase 1 — Simulateur de crédit (0.0.1)
- [x] Simulateur, comparateur, remboursement anticipé, capacité d'emprunt
- [x] Quotas journaliers, historique, exports PDF / Word / Excel, partage par lien
- [x] README propre au projet, licence MIT

## Phase 2 — Connexion via Portier (0.1.0)
- [x] Client OpenID Connect sans dépendance (code + PKCE S256, state, nonce, RS256 par JWKS)
- [x] Routes `/auth/portier` et `/auth/portier/retour`, échange `/api/auth/portier/echange`
- [x] Compte local trouvé par e-mail vérifié ; 404 si désactivé
- [x] Bouton « Se connecter via Portier » sur `/login` (si `PORTIER_ACTIF=true`)
- [x] Tests avec un faux fournisseur (`tests/Feature/PortierTest.php`)
- [x] Documentation (`docs/portier.md`, README, `.env.example`)
- [ ] Déclarer le client `loan` dans Portier et remplir `.env` en production (Charles)

## Phase 3 — Inscription via Portier (0.2.0)
- [x] Création du compte au retour de Portier si l'e-mail vérifié est inconnu (règles de l'inscription habituelle)
- [x] Bouton « S'inscrire via Portier » sur `/register`
- [x] Tests (création, pas de doublon, e-mail absent / invalide / non vérifié)
- [ ] Réinitialisation du mot de passe (« Mot de passe oublié ? » est encore inactif)
