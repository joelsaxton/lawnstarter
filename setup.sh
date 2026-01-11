#!/usr/bin/env bash

set -e

echo "🚀 Setting up Lawnstarter project..."
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Copying .env.example to .env..."
    cp .env.example .env
    echo "WWWUSER=$(id -u)" >> .env
    echo "WWWGROUP=$(id -g)" >> .env
    echo "✅ .env created with user/group IDs"
else
    echo "⚠️  .env already exists, skipping..."
fi

echo ""

# Check if backend/.env exists
if [ ! -f backend/.env ]; then
    echo "📝 Copying backend/.env.example to backend/.env..."
    cp backend/.env.example backend/.env
    echo "✅ backend/.env created"
else
    echo "⚠️  backend/.env already exists, skipping..."
fi

echo ""

# Install Laravel dependencies
if [ ! -d backend/vendor ]; then
    echo "📦 Installing Laravel dependencies..."
    echo "This may take a few minutes..."
    cd backend
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php83-composer:latest \
        composer install --ignore-platform-reqs
    cd ..
    echo "✅ Laravel dependencies installed"
else
    echo "⚠️  backend/vendor already exists, skipping composer install..."
fi

echo ""
echo "✅ Setup complete!"
echo ""
echo "Next steps:"
echo "1. Configure Sail alias (if not already done):"
echo "   echo \"alias sail='sh \\\$([ -f sail ] && echo sail || echo backend/vendor/bin/sail)'\" >> ~/.zshrc"
echo "   source ~/.zshrc"
echo ""
echo "2. Start the application:"
echo "   sail up -d"
echo ""
echo "3. Run migrations:"
echo "   sail artisan migrate"
echo ""