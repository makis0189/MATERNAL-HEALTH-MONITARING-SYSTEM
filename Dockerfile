FROM php:8.2-apache

# Enable the mysqli extension used by db.php
RUN docker-php-ext-install mysqli

# Enable .htaccess support
RUN a2enmod rewrite

# Make the default VirtualHost match any port, so ports.conf is the
# only file we need to touch at runtime.
RUN sed -i 's/\*:80/\*:*/' /etc/apache2/sites-enabled/000-default.conf

COPY . /var/www/html/

# At container start:
#  1. Force-disable any threaded MPMs and remove their leftover
#     symlinks (fixes "More than one MPM loaded" seen on some hosts),
#     then enable prefork, which mod_php requires.
#  2. Bind Apache to Railway's dynamic $PORT by overwriting (not
#     editing) ports.conf, safe to run on every restart.
#  3. Test the config (apache2ctl -t) so any remaining issue prints
#     a clear message in the logs instead of a silent crash.
RUN printf '#!/bin/bash\nset -e\na2dismod mpm_event mpm_worker 2>/dev/null || true\nrm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* 2>/dev/null || true\na2enmod mpm_prefork 2>/dev/null || true\necho "Listen ${PORT:-80}" > /etc/apache2/ports.conf\napache2ctl -t\nexec apache2-foreground\n' > /entrypoint.sh \
    && chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
