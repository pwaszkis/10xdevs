#!/bin/bash

# Script to initialize a fresh Laravel 11 project
# Run this if you're starting from scratch without Laravel installed

set -e

echo "🚀 Initializing Laravel 11 project..."

# Check if we're in a Docker container
if [ -f /.dockerenv ]; then
    DOCKER_EXEC=""
else
    DOCKER_EXEC="docker compose run --rm app"
fi

# Check if Laravel is already installed
if [ -f "artisan" ]; then
    echo "⚠️  Laravel is already installed!"
    read -p "Do you want to continue anyway? This will overwrite files. (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "📦 Installing Laravel 11..."
$DOCKER_EXEC composer create-project laravel/laravel tmp
cp -r tmp/. .
rm -rf tmp

echo "📦 Installing Livewire..."
$DOCKER_EXEC composer require livewire/livewire

echo "📦 Installing Laravel Breeze..."
$DOCKER_EXEC composer require laravel/breeze --dev
$DOCKER_EXEC php artisan breeze:install blade

echo "📦 Installing additional packages..."
$DOCKER_EXEC composer require laravel/socialite
$DOCKER_EXEC composer require wireui/wireui
$DOCKER_EXEC composer require openai-php/laravel
$DOCKER_EXEC composer require spatie/laravel-pdf

echo "📦 Installing dev dependencies..."
$DOCKER_EXEC composer require --dev larastan/larastan
$DOCKER_EXEC composer require --dev friendsofphp/php-cs-fixer
$DOCKER_EXEC composer require --dev squizlabs/php_codesniffer

echo "📦 Installing npm dependencies..."
docker compose run --rm node npm install

echo "🔑 Generating application key..."
$DOCKER_EXEC php artisan key:generate

echo "🗄️  Running migrations..."
$DOCKER_EXEC php artisan migrate

echo "🔗 Creating storage link..."
$DOCKER_EXEC php artisan storage:link

echo ""
echo "✅ Laravel initialization complete!"
echo ""
echo "Next steps:"
echo "  1. Configure your .env file"
echo "  2. Run: make up"
echo "  3. Visit: http://localhost"
echo ""
