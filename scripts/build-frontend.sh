#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Laravel Cloud execute ce script depuis la racine Laravel. Le bundle React est
# copie dans public afin que le frontend et l'API partagent le meme domaine.
cd "$ROOT_DIR/frontend"
npm ci
VITE_API_BASE_URL=/api/v1 npm run build

rm -rf "$ROOT_DIR/public/assets"
cp -R dist/. "$ROOT_DIR/public/"
