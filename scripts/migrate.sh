#!/bin/bash
# Roda migrations (com --force para ambiente de produção)
# Uso:
#   bash scripts/migrate.sh            -> migrate normal
#   bash scripts/migrate.sh rollback   -> rollback 1 passo
#   bash scripts/migrate.sh status     -> status das migrations
#   bash scripts/migrate.sh fresh      -> APAGA TUDO e recria (perigoso!)

PHP=/opt/alt/php84/usr/bin/php
DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$DIR"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

ACTION=${1:-run}

case "$ACTION" in
  run|"")
    echo -e "${YELLOW}Rodando migrations...${NC}"
    "$PHP" artisan migrate --force && echo -e "${GREEN}✔ Migrations concluídas.${NC}"
    ;;
  rollback)
    STEPS=${2:-1}
    echo -e "${YELLOW}Rollback de ${STEPS} migration(s)...${NC}"
    "$PHP" artisan migrate:rollback --force --step="$STEPS" && echo -e "${GREEN}✔ Rollback concluído.${NC}"
    ;;
  status)
    "$PHP" artisan migrate:status
    ;;
  fresh)
    echo -e "${RED}ATENÇÃO: isso apaga TODOS os dados do banco!${NC}"
    read -p "Confirma? (digite 'sim' para continuar): " CONFIRM
    if [ "$CONFIRM" = "sim" ]; then
      "$PHP" artisan migrate:fresh --force && echo -e "${GREEN}✔ Banco recriado.${NC}"
    else
      echo "Cancelado."
    fi
    ;;
  *)
    echo "Uso: bash scripts/migrate.sh [run|rollback [N]|status|fresh]"
    exit 1
    ;;
esac
