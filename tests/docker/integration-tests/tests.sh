#!/bin/sh

# start the actual tests
echo Start Collaboration app integration tests
echo sleeping 60s ... giving nextcloud time to startup && sleep 30

echo
echo Testing the OCS api of nc-1.nl, nc-2.nl:
echo ----------------------------------------
echo 
curl -u admin:${ADMIN_PASS} -H 'OCS-APIRequest: true' http://nc-1.nl/ocs/v2.php/core/getapppassword
echo 
curl -u admin:${ADMIN_PASS} -H 'OCS-APIRequest: true' http://nc-2.nl/ocs/v2.php/core/getapppassword

echo 
echo
echo "Starting integration unit tests"
cd /tmp/tests/src
./vendor/phpunit/phpunit/phpunit -c phpunit.xml
# and exit with the phpunit exit code
exit $?

