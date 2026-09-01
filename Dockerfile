FROM php:8.2-apache

# Enable the mysqli extension used by db.php
RUN docker-php-ext-install mysqli

# Copy the project files into Apache's web root
COPY . /var/www/html/

# Allow .htaccess overrides (harmless even if not used)
RUN a2enmod rewrite

# Railway assigns a dynamic $PORT at runtime; Apache defaults to port 80.
# This entrypoint rewrites Apache's config to listen on $PORT before starting.
RUN printf '#!/bin/bash\nsed -i "s/80/${PORT:-80}/g" /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf\nexec apache2-foreground\n' > /entrypoint.sh \
    && chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]