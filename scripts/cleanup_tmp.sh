#!/bin/sh
# cleanup_tmp.sh
# Vymaže súbory v $TMP_DIR staršie ako N minút (default 1440 = 24h).
# Podporuje:
#   --minutes N    (alebo)  --days D
#   --dry-run      (iba vypíše, nemaže)
#
# Použitie:
#   bash /usr/local/bin/cleanup_tmp.sh --minutes 60
#   bash /usr/local/bin/cleanup_tmp.sh --days 1
#   bash /usr/local/bin/cleanup_tmp.sh --dry-run

set -eu

# Defaulty
TMP_DIR="${TMP_DIR:-/var/www/html/tmp}"
MINUTES=1440   # default: 24h
DRY_RUN=0

# Parse args (very small parser)
while [ $# -gt 0 ]; do
  case "$1" in
    --minutes)
      shift
      MINUTES="$1"
      ;;
    --days)
      shift
      # 1 day = 1440 minutes
      MINUTES=$(expr "$1" \* 1440)
      ;;
    --dry-run)
      DRY_RUN=1
      ;;
    --help|-h)
      echo "Usage: $0 [--minutes N] [--days D] [--dry-run]"
      exit 0
      ;;
    *)
      echo "Unknown arg: $1" >&2
      exit 2
      ;;
  esac
  shift
done

# Timestamp
TS="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
echo "[$TS] cleanup_tmp.sh starting. TMP_DIR=$TMP_DIR, older than ${MINUTES}min, dry_run=$DRY_RUN"

if [ ! -d "$TMP_DIR" ]; then
  echo "[$TS] Dir $TMP_DIR does not exist — nothing to do."
  exit 0
fi

# Find files older than MINUTES and delete (safe: list first)
echo "[$TS] Finding files older than ${MINUTES} minutes in $TMP_DIR ..."
# Use -type f to match files only
if [ "$DRY_RUN" -eq 1 ]; then
  find "$TMP_DIR" -type f -mmin +"$MINUTES" -print
  echo "[$TS] Dry-run: no files were deleted."
  exit 0
fi

# Delete files (print each deleted file)
find "$TMP_DIR" -type f -mmin +"$MINUTES" -print -exec rm -f {} \;

# Optionally remove empty subdirectories
echo "[$TS] Removing empty directories (if any) under $TMP_DIR ..."
find "$TMP_DIR" -type d -empty -delete || true

echo "[$TS] cleanup_tmp.sh finished."
exit 0
