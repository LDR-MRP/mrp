#!/bin/bash
set -e

# Asegurar directorios requeridos
mkdir -p Assets/uploads Uploads Logs

# Iniciar PHP-FPM
exec php-fpm
