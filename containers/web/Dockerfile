# web/Dockerfile
# Using a Debian-based PHP-Apache image
FROM php:8.3-apache-bookworm

# Install common PHP extensions (adjust as needed for your app)
RUN docker-php-ext-install pdo_mysql mysqli # Example: install mysql extensions
RUN docker-php-ext-enable pdo_mysql mysqli # Example: enable mysql extensions

# If you need to install more system packages (e.g., git, zip, unzip)
RUN apt-get update && apt-get install -y \
     git \
     zip \
     unzip \
     && rm -rf /var/lib/apt/lists/*


COPY server.crt /etc/apache2/ssl/server.crt
COPY server.key /etc/apache2/ssl/server.key

COPY sboanalytics.conf /etc/apache2/sites-available/sboanalytics.conf

RUN a2enmod ssl 

RUN a2ensite sboanalytics

# Enable Apache rewrite module (often needed for frameworks)
RUN a2enmod rewrite

# Expose port 80 (HTTP) and 443 (HTTPS) - Apache listens on these
EXPOSE 80
EXPOSE 443

# The base image already sets up Apache to run,
# so no CMD or ENTRYPOINT is typically needed.
