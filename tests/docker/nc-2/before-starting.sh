#!/bin/sh

status=$?;
cmd=php /var/www/html/occ maintenance:mode;
status=$?;

# status 0 means nextcloud already installed
if [ $status -eq 1 ]
then
    echo 'Setup nextcloud database';
    php /var/www/html/occ maintenance:install --database mysql --database-name nextcloud --database-host mariadb-nc-2 --database-user nextcloud --database-pass nextcloud --admin-user admin --admin-pass ${ADMIN_PASS} --admin-email admin@nc-2.nl;
    # add necessary config.php settings
    sed -i "/^);/i 'loglevel' => 0, 'trusted_proxies' => [0 => '10.1.0.100'], 'overwritehost' => 'nc-2.nl', 'overwriteprotocol' => 'https'" /var/www/html/config/config.php;

    # add users
    export OC_PASS=d_mysecretpass
    php /var/www/html/occ user:add --password-from-env --display-name="Dan Janssen" --group="users" dan;
    export OC_PASS=p_mysecretpass
    php /var/www/html/occ user:add --password-from-env --display-name="Pete Peterson" --group="users" pete;

    # Install the Collaboration app
    echo 'Install Collaboration app';
    tar xvf /tmp/collaboration/build/artifacts/app/collaboration_test.tar.gz -C /var/www/html/apps;
    php /var/www/html/occ app:enable collaboration;
else
    echo 'Nextcloud already installed'
fi
