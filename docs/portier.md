# Connexion via Portier

Loan peut proposer **« Se connecter via Portier »** en plus de sa connexion par e-mail et mot de
passe, qui reste identique. [Portier](https://portier.mawena.cloud) est le fournisseur
OpenID Connect de mawena.cloud (connexion unique, mot de passe + double facteur).

## Fonctionnement
1. Le bouton de `/login` mène à `GET /auth/portier`, qui garde en session `state`, `nonce` et le
   vérificateur PKCE, puis redirige vers `https://portier.mawena.cloud/oidc/autoriser`
   (`response_type=code`, portées `openid profile email`, PKCE S256).
2. Portier renvoie vers `GET /auth/portier/retour?code=…&state=…`. Loan vérifie le `state`,
   échange le code à `/oidc/jeton` (secret client dans le corps, `client_secret_post`, et
   `code_verifier`), puis vérifie le jeton d'identité : signature RS256 avec la clé du JWKS
   `/oidc/cles` (mis en cache une heure, rechargé si le `kid` est inconnu), `iss`, `aud`
   (et `azp`), `exp`, `nonce`.
3. Le compte local est celui dont l'adresse e-mail est égale à la revendication `email`
   (`email_verified` doit être vrai). **Aucun compte n'est créé** : sans compte correspondant,
   retour sur `/login` avec un message.
4. Loan étant une SPA authentifiée par jeton Sanctum, le retour ne pose pas de session : il
   renvoie vers `/login?portier=<code>` (code aléatoire à usage unique, valable 60 s, gardé dans
   le cache). La page de connexion l'échange par `POST /api/auth/portier/echange` contre le
   même jeton Sanctum que `POST /api/auth/login`, puis continue comme une connexion habituelle.

Erreurs affichées sur `/login` (`?portier_erreur=`) : `etat` (state absent ou faux, session
expirée), `refus` (Portier a refusé l'accès, `access_denied`), `sans_email`, `compte_inconnu`,
`echec` (échange, signature ou revendications invalides ; le détail est dans le journal Laravel).

Pas de jeton de rafraîchissement : la session Loan est ensuite celle du jeton Sanctum.
La déconnexion de Loan ne ferme pas la session Portier (et inversement).

## Réglages (`.env`)
```
PORTIER_ACTIF=true
PORTIER_ISSUER=https://portier.mawena.cloud
PORTIER_CLIENT_ID=loan
PORTIER_CLIENT_SECRET=<secret donné par « portier client-secret »>
PORTIER_REDIRECT_URI=          # vide : APP_URL + /auth/portier/retour
```
`PORTIER_ACTIF=false` (défaut) : aucun bouton, et `/auth/portier`, `/auth/portier/retour`,
`/api/auth/portier/echange` répondent 404.

## Déclaration dans Portier
Adresse de retour exacte (production, `APP_URL=https://loan.mawena.cloud`) :

```
https://loan.mawena.cloud/auth/portier/retour
```

```toml
[[clients]]
id = "loan"
nom = "Loan"
secret_sha256 = "<empreinte donnée par portier client-secret>"
redirections = ["https://loan.mawena.cloud/auth/portier/retour"]
acces = ["charles-gamligo"]   # ou "*"
```
Loan n'utilise pas la déconnexion OpenID Connect : aucune adresse `deconnexions` n'est
nécessaire. Chaque personne doit avoir dans Portier l'adresse e-mail de son compte Loan :
`portier courriel <nom> <adresse>`.
