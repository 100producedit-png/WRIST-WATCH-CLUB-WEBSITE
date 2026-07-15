#!/usr/bin/env bash
# Zips shopify/theme into an uploadable Shopify theme package.
# Usage: tools/package_theme.sh   →  dist/wrist-watch-club-theme.zip
set -euo pipefail
repo="$(cd "$(dirname "$0")/.." && pwd)"
out="$repo/dist/wrist-watch-club-theme.zip"
mkdir -p "$repo/dist"
rm -f "$out"
(cd "$repo/shopify/theme" && zip -r "$out" assets config layout locales sections snippets templates -x '*.DS_Store')
echo "Wrote $out"
