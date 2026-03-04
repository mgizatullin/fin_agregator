#!/usr/bin/env bash
# Запуск миграций (выполнять в WSL: bash run-migrations.sh)
cd "$(dirname "$0")"
php artisan migrate --force
echo "Готово. Проверьте вывод выше."
