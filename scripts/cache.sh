#!/bin/bash
# Recria todos os caches do Laravel
# Uso: bash scripts/cache.sh

PHP=/opt/alt/php84/usr/bin/php
DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$DIR"

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Recriando caches...${NC}"

"$PHP" artisan config:cache && echo -e "${GREEN}✔ config${NC}"
"$PHP" artisan route:cache  && echo -e "${GREEN}✔ route${NC}"
"$PHP" artisan view:cache   && echo -e "${GREEN}✔ view${NC}"
"$PHP" artisan optimize     && echo -e "${GREEN}✔ optimize${NC}"

echo -e "${GREEN}Caches recriados.${NC}"
