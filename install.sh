#!/bin/bash
#
# Script de instalación del SCP - Sistema de Cocina de Producción
#
# Uso:
#   1. Crear un proyecto Laravel nuevo: composer create-project laravel/laravel scp-cocina
#   2. Copiar este paquete ENCIMA del proyecto recién creado
#   3. Ejecutar: bash install.sh
#

set -e

echo "=========================================="
echo "  SCP - Instalación de dependencias"
echo "=========================================="
echo ""

# Verificar PHP
echo "→ Verificando PHP..."
PHP_VERSION=$(php8.3 -r 'echo PHP_VERSION;')
PHP_MAJOR=$(php8.3 -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$(php8.3 -r 'echo PHP_MINOR_VERSION;')

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 2 ]); then
    echo "  ✗ ERROR: Se requiere PHP 8.2+ (tienes $PHP_VERSION)"
    exit 1
fi
echo "  ✓ PHP $PHP_VERSION"

# Verificar Composer
echo "→ Verificando Composer..."
if ! command -v composer &> /dev/null; then
    echo "  ✗ ERROR: Composer no está instalado"
    exit 1
fi
echo "  ✓ Composer instalado"

# Verificar Node
echo "→ Verificando Node..."
if ! command -v node &> /dev/null; then
    echo "  ✗ ERROR: Node.js no está instalado"
    exit 1
fi
NODE_VERSION=$(node -v)
echo "  ✓ Node $NODE_VERSION"

echo ""
echo "→ Instalando dependencias de Composer (Inertia)..."
php8.3 $(which composer) require inertiajs/inertia-laravel

echo ""
echo "→ Publicando middleware de Inertia..."
php8.3 artisan inertia:middleware

echo ""
echo "→ Instalando dependencias de NPM..."
npm install
npm install @inertiajs/vue3 vue@latest @vitejs/plugin-vue
npm install -D tailwindcss@3 postcss autoprefixer

echo ""
echo "→ Inicializando Tailwind..."
npx tailwindcss init -p

echo ""
echo "=========================================="
echo "  Instalación completada"
echo "=========================================="
echo ""
echo "Siguiente paso:"
echo ""
echo "  En una terminal:   npm run dev"
echo "  En otra terminal:  php artisan serve"
echo ""
echo "Después abre http://localhost:8000"
echo ""
