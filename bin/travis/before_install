#!/usr/bin/env bash

phpenv config-rm xdebug.ini
yes | pecl install mcrypt-snapshot
composer validate --ansi
composer global require hirak/prestissimo
