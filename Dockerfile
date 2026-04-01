FROM dunglas/frankenphp:php8.5-bookworm

WORKDIR /app

# System deps + PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip curl ca-certificates nodejs npm \
 && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    pdo_mysql mbstring intl curl dom fileinfo openssl tokenizer xml

# Copy app
COPY . .

# Install PHP deps
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Build frontend
RUN npm ci && npm run build

# Laravel caches (safe in container build)
RUN php artisan config:cache \
 && php artisan route:cache \
 && php artisan view:cache

 EXPOSE 8080
 ENV PORT=8080
 
 CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]