#!/usr/bin/env bash

# Travis CI "before_install" step script.

phpenv config-rm xdebug.ini
yes | pecl install mcrypt-snapshot
composer validate --ansi
composer global require hirak/prestissimo
