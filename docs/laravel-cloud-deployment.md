# Deploiement Laravel Cloud

L'application Laravel se trouve maintenant a la racine du depot et le frontend
React reste dans `frontend/`. Laravel Cloud peut donc detecter directement le
framework Laravel depuis le depot importe.

Dans les reglages de l'environnement de production :

1. Importer le depot GitHub et selectionner la branche `main`.
2. Attacher une base PostgreSQL Laravel Cloud et un cache Redis/KV Store.
3. Selectionner une version Node recente (20 ou superieure).
4. Ajouter `bash scripts/build-frontend.sh` aux **Build commands** pour compiler
   le frontend React dans `public`.
5. Ajouter `php artisan migrate --force` aux **Deploy commands**.
6. Definir au minimum : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` avec
   le domaine Cloud, `SESSION_DRIVER=database`, `CACHE_STORE=database` et
   `QUEUE_CONNECTION=database`. Laisser Laravel Cloud injecter les identifiants
   de la base attachee.
7. Lors du premier deploiement seulement, lancer dans la console Cloud :
   `php artisan db:seed --force`.

## Indexation Google

Le fichier `public/robots.txt` autorise tous les robots avec
`Allow: /`. Le frontend fournit une balise `robots` avec `index,follow` et
aucune directive `noindex` n'est ajoutee par l'application.

Verifier apres deploiement :

```text
https://votre-domaine/robots.txt
https://votre-domaine/
https://votre-domaine/api/v1/regles
```

Le site doit etre servi en HTTPS par Laravel Cloud. Pour les appels audio WebRTC
en production, configurer aussi `VITE_WEBRTC_ICE_SERVERS` avec un serveur TURN.
