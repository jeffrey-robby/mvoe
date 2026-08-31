#!/usr/bin/env bash
#
# Deploiement de Mvoe.
#
# A lancer DEPUIS LA RACINE du projet sur le serveur.
#
# Deux choses ne voyagent pas par git et doivent donc etre refaites ici :
#   - public/build/ : tout le CSS et le JS. Sans lui, aucune regle Tailwind
#     n'arrive au navigateur et le service worker precache 89 URL en 404.
#   - les dossiers de cache de storage/ : git ne transporte pas un dossier
#     vide, et Blade refuse de compiler sans storage/framework/views.

set -euo pipefail

echo "→ Code"
git pull --ff-only

echo "→ Dependances PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Dossiers de travail"
# Refaits a chaque fois : une restauration ou un rsync peut les avoir perdus.
mkdir -p storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "→ Assets"
# public/hot est le drapeau depose par `npm run dev`. Tant qu'il existe,
# Laravel sert le CSS depuis 127.0.0.1:5174 — la machine du VISITEUR — et
# ignore public/build. Les pages s'affichent alors sans une seule regle de
# style. Il n'arrive jamais par git ; il vient d'un envoi FTP ou rsync du
# dossier complet depuis un poste de developpement.
rm -f public/hot

# `npm run build` enchaine vite build PUIS generer-sw.mjs. Ne jamais lancer
# l'un sans l'autre : le service worker nommerait les hachages du build
# precedent, et le kit serait vide en mode avion.
npm ci
npm run build

echo "→ Base de donnees"
php artisan migrate --force

echo "→ Caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

echo "✓ Deploiement termine"
