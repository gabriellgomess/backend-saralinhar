#!/bin/bash
# Wrapper para rodar qualquer comando artisan
# Uso: bash scripts/artisan.sh <comando>
# Exemplo: bash scripts/artisan.sh migrate
#          bash scripts/artisan.sh make:model Foo

PHP=/opt/alt/php84/usr/bin/php
DIR="$(cd "$(dirname "$0")/.." && pwd)"

if [ -z "$1" ]; then
  echo "Uso: bash scripts/artisan.sh <comando artisan>"
  echo "Exemplo: bash scripts/artisan.sh migrate"
  exit 1
fi

cd "$DIR"
echo ">> php artisan $*"
"$PHP" artisan "$@"
