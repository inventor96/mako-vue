FROM adminer:latest

USER root
COPY plugins/*.php /var/www/html/plugins/
RUN chmod 644 /var/www/html/plugins/*.php
USER adminer