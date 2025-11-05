#!/usr/bin/env bash
# cleanup_tmp.sh
# Vymaže súbory v TMP_DIR staršie než N dní.
# Podpora parametrov:
#   --days=N      (počet dní, default 1)
#   --dir=PATH    (adresár, default /var/www/html/tmp)
#   --dry-run     (len vypíše, čo by vymazal)
#   --help        (zobraziť pomoc)

set -euo pipefail
IFS=$'\n\t'

# Defaults
TMP_DIR="/var/www/html/tmp"
DAYS=1
DRY_RUN=0

usage() {
  cat <<EOF
Usage: $0 [--days=N] [--dir=PATH] [--dry-run] [--help]

--days=N     Delete files older than N days (default: 1)
--dir=PATH   Directory to clean (default: /var/www/html/tmp)
--dry-run    Don't delete, just print what would be removed
--help       Show this help
EOF
}

# Parse args
while [ "$#" -gt 0 ]; do
  case "$1" in
    --days=*) DAYS="${1#*=}"; shift ;;
    --days) DAYS="$2"; shift 2 ;;
    --dir=*) TMP_DIR="${1#*=}"; shift ;;
    --dir) TMP_DIR="$2"; shift 2 ;;
    --dry-run) DRY_RUN=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1"; usage; exit 2 ;;
  esac
done

# Validate numeric days
if ! [[ "$DAYS" =~ ^[0-9]+$ ]]; then
  echo "Invalid --days value: $DAYS (must be integer)"; exit 2
fi

# Safety checks
if [ -z "$TMP_DIR" ]; then
  echo "Refusing to run: TMP_DIR is empty"; exit 2
fi
# don't allow root
case "$TMP_DIR" in
  "/"|"/root"|"")
    echo "Refusing to run: TMP_DIR looks dangerous: ${TMP_DIR}"; exit 2
    ;;
esac

MINUTES=$((DAYS * 1440))

timestamp() { date '+%Y-%m-%d %H:%M:%S'; }

echo "$(timestamp) cleanup_tmp.sh starting. TMP_DIR=${TMP_DIR}, older than ${DAYS} day(s) (${MINUTES} minutes), dry_run=${DRY_RUN}"

if [ ! -d "$TMP_DIR" ]; then
  echo "$(timestamp) Dir ${TMP_DIR} does not exist, nothing to clean."
  exit 0
fi

# Use -type f, -mmin +N, handle weird filenames with -print0
echo "$(timestamp) Finding files older than ${MINUTES} minutes in ${TMP_DIR} ..."
if [ "$DRY_RUN" -eq 1 ]; then
  find "$TMP_DIR" -type f -mmin +"$MINUTES" -print0 | xargs -0 -r -I{} printf "%s\n" "{}"
else
  # remove files (use xargs for batching)
  find "$TMP_DIR" -type f -mmin +"$MINUTES" -print0 | xargs -0 -r rm -f --
fi

# Optionally remove empty directories (safe)
echo "$(timestamp) Removing empty directories (if any) under ${TMP_DIR} ..."
if [ "$DRY_RUN" -eq 1 ]; then
  find "$TMP_DIR" -type d -empty -print
else
  find "$TMP_DIR" -type d -empty -delete || true
fi

echo "$(timestamp) cleanup_tmp.sh finished."
exit 0
