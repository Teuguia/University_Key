# Sécurité backend University Key

Ce document résume les mesures mises en place ou attendues côté backend.

## Authentification

- Laravel Sanctum est utilisé pour l'API via tokens Bearer.
- Les routes publiques sont :
  - `POST /api/v1/auth/register`
  - `POST /api/v1/auth/login`
- Les routes privées sont protégées par `auth:sanctum` :
  - `GET /api/v1/auth/me`
  - `POST /api/v1/auth/logout`

## Hash des mots de passe

- Les mots de passe sont hachés avec `Hash::make()` dans `AuthController`.
- Le modèle `User` conserve aussi le cast Laravel `password => hashed` comme protection supplémentaire.
- Aucun mot de passe brut n'est journalisé ou retourné par l'API.

## Validation des formulaires

- `RegisterRequest` valide :
  - `prenom`
  - `nom`
  - `email`
  - `telephone`
  - `password`
  - `password_confirmation`
  - `conditions_acceptees`
- `LoginRequest` valide :
  - `email`, qui peut contenir l'e-mail ou le numéro de téléphone
  - `password`

## Protection CSRF, XSS et SQL injection

- Les routes API utilisent des tokens Bearer Sanctum. Pour les formulaires web classiques, Laravel garde sa protection CSRF native.
- Les requêtes base de données passent par Eloquent ou Query Builder, ce qui protège contre l'injection SQL quand on évite les chaînes SQL manuelles.
- Les réponses API sont JSON. Côté React, le texte est rendu comme texte et non comme HTML brut, ce qui réduit le risque XSS.
- Ne jamais utiliser `dangerouslySetInnerHTML` avec du contenu utilisateur non nettoyé.

## Rate limiting

- Les endpoints `register` et `login` utilisent `throttle:auth`.
- La limite actuelle est de 5 tentatives par minute par identifiant + IP.
- La règle est définie dans `AppServiceProvider`.

## Gates et policies

- Le gate `access-active-account` bloque les comptes dont `statut` n'est pas `actif`.
- Les routes privées utilisent ce gate avec le middleware `can:access-active-account`.
- Pour les futurs modules admin, créer des gates/policies spécifiques, par exemple `manage-users`, `validate-counselor`, `publish-content`.

## Logs

- Les événements suivants sont journalisés sans données sensibles :
  - inscription réussie
  - connexion réussie
  - échec de connexion
  - compte bloqué par statut
  - déconnexion
- Les logs Laravel utilisent la configuration `config/logging.php`.

## Sauvegarde base de données

- Une commande Artisan existe :
  - `php artisan db:backup`
- Elle est prévue pour PostgreSQL et écrit les dumps dans :
  - `storage/app/backups`
- Sur serveur, planifier cette commande avec cron ou le scheduler Laravel.
- Les fichiers de sauvegarde doivent être chiffrés, stockés hors serveur et testés régulièrement par restauration.

## HTTPS / SSL ready

- En production, servir l'API uniquement en HTTPS.
- Configurer `APP_URL=https://...`.
- Configurer le reverse proxy pour transmettre `X-Forwarded-Proto`.
- Définir les cookies en mode sécurisé pour les flux cookie/session.
- Sanctum token Bearer fonctionne aussi derrière HTTPS sans changement de code.

## Pare-feu serveur

- Ouvrir uniquement les ports nécessaires :
  - 80/443 pour HTTP/HTTPS
  - SSH limité aux IP administrateur si possible
  - PostgreSQL non exposé publiquement
- Activer un pare-feu système, par exemple UFW sous Ubuntu.
- Exemple indicatif :
  - `ufw default deny incoming`
  - `ufw default allow outgoing`
  - `ufw allow 443/tcp`
  - `ufw allow from <IP_ADMIN> to any port 22 proto tcp`
  - `ufw enable`

## Points à faire avant production

- Configurer un vrai envoi e-mail/SMS pour les `codes_verification`.
- Ajouter une rotation automatique des sauvegardes.
- Ajouter une surveillance d'erreurs et d'activité suspecte.
- Configurer CORS selon le vrai domaine frontend.
- Activer une politique de mots de passe plus forte si le contexte l'exige.
