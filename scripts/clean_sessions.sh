#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Limpieza segura de la carpeta de sesiones de WhatsApp (baileys).
# Borra SOLO:
#   1) archivos .tmp de store (basura de escrituras atómicas) con >60 min.
#   2) device_N_store.json de números que YA NO existen en la BD (huérfanos).
# NUNCA borra:
#   - md_device_N (autenticación de un número) → borrarlo desconectaría el número.
#   - sesiones de números que sí existen en la BD.
# Protección: si la BD no responde (lista de IDs vacía), NO borra huérfanos.
# ─────────────────────────────────────────────────────────────────────────────
set -u
APP_DIR="/var/www/html/whatstar"
SESS="$APP_DIR/sessions"
LOG="$APP_DIR/storage/logs/clean_sessions.log"
cd "$APP_DIR" || exit 1

ts() { date '+%Y-%m-%d %H:%M:%S'; }

# --- 1) Basura .tmp (siempre seguro; no depende de la BD) ---
TMP_DELETED=$(find "$SESS" -maxdepth 1 -name '*.json.tmp.*' -mmin +60 -print -delete 2>/dev/null | wc -l)

# --- 2) Huérfanos device_N_store.json (solo si la BD responde) ---
DB_DATABASE=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_USERNAME=$(grep -E '^DB_USERNAME=' .env | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_PASSWORD=$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2- | tr -d '"' | tr -d "'")
DB_HOST=$(grep -E '^DB_HOST=' .env | cut -d= -f2- | tr -d '"' | tr -d "'"); DB_HOST=${DB_HOST:-127.0.0.1}

DBIDS=$(mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -N -e "SELECT id FROM devices;" 2>/dev/null)

ORPH=0
if [ -n "$DBIDS" ]; then
    IDSET=" $(echo "$DBIDS" | tr '\n' ' ') "
    for f in $(ls "$SESS" 2>/dev/null | grep -E '^device_[0-9]+_store\.json$'); do
        id=$(echo "$f" | grep -oE '[0-9]+' | head -1)
        if ! echo "$IDSET" | grep -q " $id "; then
            rm -f "$SESS/$f" && ORPH=$((ORPH+1))
        fi
    done
    ORPH_MSG="$ORPH"
else
    ORPH_MSG="omitido (BD no accesible)"
fi

SIZE=$(du -sh "$SESS" 2>/dev/null | cut -f1)
echo "$(ts) | tmp_borrados=$TMP_DELETED | store_huerfanos_borrados=$ORPH_MSG | sessions=$SIZE" >> "$LOG"
