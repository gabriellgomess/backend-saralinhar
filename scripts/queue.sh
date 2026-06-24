#!/bin/bash
# Gerencia as filas do Laravel
# Uso:
#   bash scripts/queue.sh restart   -> reinicia os workers
#   bash scripts/queue.sh retry     -> recoloca jobs falhos na fila
#   bash scripts/queue.sh failed    -> lista jobs falhos
#   bash scripts/queue.sh flush     -> apaga todos os jobs falhos

PHP=/opt/alt/php84/usr/bin/php
DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$DIR"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ACTION=${1:-restart}

case "$ACTION" in
  restart)
    echo -e "${YELLOW}Reiniciando workers de fila...${NC}"
    "$PHP" artisan queue:restart && echo -e "${GREEN}✔ Sinal de restart enviado.${NC}"
    ;;
  retry)
    echo -e "${YELLOW}Reenfileirando jobs falhos...${NC}"
    "$PHP" artisan queue:retry all && echo -e "${GREEN}✔ Jobs reenfileirados.${NC}"
    ;;
  failed)
    "$PHP" artisan queue:failed
    ;;
  flush)
    echo -e "${YELLOW}Apagando jobs falhos...${NC}"
    "$PHP" artisan queue:flush && echo -e "${GREEN}✔ Jobs falhos removidos.${NC}"
    ;;
  *)
    echo "Uso: bash scripts/queue.sh [restart|retry|failed|flush]"
    exit 1
    ;;
esac
