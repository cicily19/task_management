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
# RUN php artisan config:cache \
# && php artisan route:cache \
# && php artisan view:cache

EXPOSE 8080

CMD ["sh", "-lc", "php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]

RUN printf "display_errors=0\nlog_errors=1\nerror_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED\n" > /usr/local/etc/php/conf.d/99-no-deprecations.ini