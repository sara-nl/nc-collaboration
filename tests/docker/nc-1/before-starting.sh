#!/bin/sh

status=$?;
cmd=php /var/www/html/occ maintenance:mode;
status=$?;

# status 0 means nextcloud already installed
if [ $status -eq 1 ]
then
    echo 'Setup nextcloud database';
    php /var/www/html/occ maintenance:install --database mysql --database-name nextcloud --database-host mariadb-nc-1 --database-user nextcloud --database-pass nextcloud --admin-user admin --admin-pass passw --admin-email admin@nc-1.nl;
    # add necessary config.php settings
    sed -i "/^);/i 'loglevel' => 0, 'trusted_proxies' => [0 => '10.1.0.100'], 'overwritehost' => 'nc-1.nl', 'overwriteprotocol' => 'https'" /var/www/html/config/config.php;
    echo 'Install Invitation app';
    cd /tmp/invitation;
    make -e version=test buildapp-tests;
    tar xvf /tmp/invitation/build/artifacts/app/invitation_test.tar.gz -C /var/www/html/apps;
    # mkdir /var/www/html/log;
    # mv /var/www/html/apps/invitation/appinfo/routes.php /var/www/html/apps/invitation/appinfo/routes-main.php;
    php /var/www/html/occ app:enable invitation;
else
    echo 'Nextcloud already installed'
fi
