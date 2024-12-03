#!/bin/sh

status=$?;
cmd=php /var/www/html/occ maintenance:mode;
status=$?;

# status 0 means nextcloud already installed
if [ $status -eq 1 ]
then
    echo 'Installing ...';
    echo 'Setup nextcloud database';
    php /var/www/html/occ maintenance:install --database mysql --database-name nextcloud --database-host mariadb-nc-1 --database-user nextcloud --database-pass nextcloud --admin-user admin --admin-pass ${ADMIN_PASS} --admin-email admin@nc-1.nl;
    # add necessary config.php settings
    sed -i "/^);/i 'loglevel' => 0, 'trusted_proxies' => [0 => '10.1.0.100'], 'overwritehost' => 'nc-1.nl', 'overwriteprotocol' => 'https'" /var/www/html/config/config.php;

    # add users
    export OC_PASS=l_mysecretpass
    php /var/www/html/occ user:add --password-from-env --display-name="Lex Lexington" --group="users" lex;
    export OC_PASS=j_mysecretpass
    php /var/www/html/occ user:add --password-from-env --display-name="Jimmie Johnson" --group="users" jimmie;

    # Install the Collaboration app
    echo 'Install Collaboration app';
    tar xvf /tmp/collaboration/build/artifacts/app/collaboration_test.tar.gz -C /var/www/html/apps;
    php /var/www/html/occ app:enable collaboration;
else
    echo 'Nextcloud already installed'
fi
