#!/usr/bin/env bash
# Extract JPG frames from local sample videos into public video-thumbnails pool
# (used when MEDIA_PLACEHOLDERS_ENABLED). Re-run after adding new *.mp4 samples.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SAMPLE_DIR="$ROOT/public/media/placeholders/sample-videos"
OUT_DIR="$ROOT/public/media/placeholders/video-thumbnails"

mkdir -p "$OUT_DIR"

# Percent positions along duration (spread; avoid exact 0%/100% black/splash)
PCTS=(10 22 34 46 58 70 82)

shopt -s nullglob
for mp4 in "$SAMPLE_DIR"/*.mp4; do
  base=$(basename "$mp4" .mp4)
  dur=$(ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "$mp4")

  for pct in "${PCTS[@]}"; do
    t=$(awk -v d="$dur" -v p="$pct" 'BEGIN { printf "%.4f", (d * p) / 100.0 }')
    out="$OUT_DIR/${base}-p${pct}.jpg"
    ffmpeg -y -hide_banner -loglevel error \
      -ss "$t" -i "$mp4" \
      -frames:v 1 \
      -vf 'scale=1280:-2' \
      -q:v 4 \
      "$out"
    echo "Wrote $out"
  done

  # Keep a canonical cover matching the video stem (first frame at ~12%)
  t0=$(awk -v d="$dur" 'BEGIN { printf "%.4f", d * 0.12 }')
  ffmpeg -y -hide_banner -loglevel error \
    -ss "$t0" -i "$mp4" \
    -frames:v 1 \
    -vf 'scale=1280:-2' \
    -q:v 4 \
    "$OUT_DIR/${base}.jpg"
  echo "Updated $OUT_DIR/${base}.jpg"
done

echo "Done. Thumbnail pool: $(find "$OUT_DIR" -maxdepth 1 -type f \( -iname '*.jpg' -o -iname '*.jpeg' \) | wc -l | tr -d ' ') JPG files"
