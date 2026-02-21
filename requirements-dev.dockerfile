FROM php:8.2-cli

# Install dependencies for Composer, PCOV, SQLite, and Xdebug
RUN apt-get update && apt-get install -y git zip unzip libzip-dev libsqlite3-dev

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PCOV for high-performance code coverage
RUN pecl install pcov && docker-php-ext-enable pcov

# Install Xdebug (required by Infection for mutation testing if not using PCOV/phpdbg)
# Note: Infection can use PCOV, but sometimes Xdebug is more stable for full mutation runs.
# We'll stick with PCOV for now but ensure libzip-dev is there for potential other needs.
# Install extensions
RUN docker-php-ext-install zip pdo_sqlite

WORKDIR /app
