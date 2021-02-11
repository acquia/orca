#!/bin/sh

# NAME
#     install_composer.sh - Installs Composer 2.
#
# SYNOPSIS
#     install_composer.sh
#
# DESCRIPTION
#     Installs Composer 2 on the Acquia Cloud environment. Code taken from
#     https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
#     without modification.

EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    >&2 echo 'ERROR: Invalid installer checksum'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php
exit $RESULT
