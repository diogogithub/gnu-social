#!/bin/sh

if [ ! -e /var/www/social/config.php ]; then

    echo -e "Installing GNU social\nInstalling composer dependencies"

    cd /var/www/social

    composer install

    chmod g+w -R /var/www/social
    chown -R :www-data /var/www/social

    php /var/www/social/scripts/install_cli.php --server="${SOCIAL_DOMAIN}" --sitename="${SOCIAL_SITENAME}" \
        --host=db --fancy=yes --database="${SOCIAL_DB}" \
        --username="${SOCIAL_USER}" --password="${SOCIAL_PASSWORD}" \
        --admin-nick="${SOCIAL_ADMIN_NICK}" --admin-pass="${SOCIAL_ADMIN_PASSWORD}" || exit 1

    echo "GNU social is installed"
fi
