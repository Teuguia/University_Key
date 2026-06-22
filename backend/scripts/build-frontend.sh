#!/usr/bin/env bash

set -euo pipefail

# Laravel Cloud execute ce script depuis le dossier backend. Le bundle React est
# copie dans public afin que le frontend et l'API partagent le meme domaine.
cd ../frontend
npm ci
VITE_API_BASE_URL=/api/v1 npm run build

rm -rf ../backend/public/assets
cp -R dist/. ../backend/public/
