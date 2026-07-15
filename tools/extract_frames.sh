#!/usr/bin/env bash
# Extract scroll-scrub frame sequences from the Seedance clips.
# Usage: tools/extract_frames.sh [ffmpeg-binary]
set -euo pipefail

FF="${1:-ffmpeg}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/site/assets/frames"

extract () { # name fps width height
  local name=$1 fps=$2 w=$3 h=$4
  rm -rf "$OUT/$name"; mkdir -p "$OUT/$name"
  "$FF" -y -loglevel error -i "$ROOT/media/$name.mp4" \
    -vf "fps=$fps,scale=$w:$h:flags=lanczos" -q:v 3 \
    "$OUT/$name/frame_%04d.jpg"
  echo "$name: $(ls "$OUT/$name" | wc -l) frames"
}

extract orbit    20 1536 864
extract macro    15 1536 864
extract assembly 15 1536 864

du -sh "$OUT"/*
