#!/bin/sh

# start the actual tests
echo Start Invitation app integration tests
echo sleeping 60s ... giving owncloud time to startup && sleep 15 &&

curl -u admin:passw -H 'OCS-APIRequest: true' http://nc-1.nl/ocs/v2.php/core/getapppassword

echo 
echo
echo "Starting integration unit tests"
cd /tmp/tests/src
./vendor/phpunit/phpunit/phpunit -c phpunit.xml
# and exit with the phpunit exit code
exit $?

