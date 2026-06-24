#!/bin/bash
# Limpa todos os caches do Laravel
# Uso: bash scripts/clear.sh

PHP=/opt/alt/php84/usr/bin/php
DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$DIR"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Limpando caches...${NC}"

"$PHP" artisan cache:clear  && echo -e "${GREEN}✔ cache${NC}"
"$PHP" artisan config:clear && echo -e "${GREEN}✔ config${NC}"
"$PHP" artisan route:clear  && echo -e "${GREEN}✔ route${NC}"
"$PHP" artisan view:clear   && echo -e "${GREEN}✔ view${NC}"
"$PHP" artisan event:clear  2>/dev/null && echo -e "${GREEN}✔ events${NC}"

echo -e "${GREEN}Todos os caches limpos.${NC}"
