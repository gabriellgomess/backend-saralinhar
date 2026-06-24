#!/bin/bash
# Sequência completa pós-deploy
# Uso: bash scripts/deploy.sh

PHP=/opt/alt/php84/usr/bin/php
DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$DIR"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

step() { echo -e "\n${YELLOW}▶ $1${NC}"; }
ok()   { echo -e "${GREEN}✔ $1${NC}"; }
fail() { echo -e "${RED}✘ $1${NC}"; exit 1; }

echo -e "${YELLOW}==============================="
echo -e " Deploy - Sara Linhar API"
echo -e "===============================${NC}"

step "Limpando caches antigos..."
"$PHP" artisan cache:clear      && ok "cache:clear" || fail "cache:clear"
"$PHP" artisan config:clear     && ok "config:clear" || fail "config:clear"
"$PHP" artisan route:clear      && ok "route:clear" || fail "route:clear"
"$PHP" artisan view:clear       && ok "view:clear" || fail "view:clear"

step "Rodando migrations..."
"$PHP" artisan migrate --force  && ok "migrate" || fail "migrate"

step "Recriando caches..."
"$PHP" artisan config:cache     && ok "config:cache" || fail "config:cache"
"$PHP" artisan route:cache      && ok "route:cache" || fail "route:cache"
"$PHP" artisan view:cache       && ok "view:cache" || fail "view:cache"

step "Otimizando..."
"$PHP" artisan optimize          && ok "optimize" || fail "optimize"

step "Verificando link de storage..."
"$PHP" artisan storage:link --force 2>/dev/null && ok "storage:link" || echo "  (link já existe ou ignorado)"

step "Reiniciando filas..."
"$PHP" artisan queue:restart     && ok "queue:restart" || echo "  (sem worker de fila ativo)"

echo -e "\n${GREEN}==============================="
echo -e " Deploy concluído com sucesso!"
echo -e "===============================${NC}\n"
