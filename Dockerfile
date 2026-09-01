FROM php:8.2-apache

# Enable the mysqli extension used by db.php
RUN docker-php-ext-install mysqli

# Enable .htaccess support
RUN a2enmod rewrite

# Fix "More than one MPM loaded" (seen on some hosts, incl. Railway):
# force prefork, which is the MPM required by mod_php.
RUN a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork

# Make the default VirtualHost match any port. This means we only
# ever need to touch ports.conf at container start, never this file.
RUN sed -i 's/\*:80/\*:*/' /etc/apache2/sites-enabled/000-default.conf

COPY . /var/www/html/

# At container start, bind Apache to Railway's dynamic $PORT.
# This OVERWRITES ports.conf (rather than find/replace) so it's
# safe to run on every restart, even if the same container/filesystem
# is reused — no risk of compounding/corrupting the file over time.
RUN printf '#!/bin/bash\nset -e\necho "Listen ${PORT:-80}" > /etc/apache2/ports.conf\nexec apache2-foreground\n' > /entrypoint.sh \
    && chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
