#!/usr/bin/env bash

# NAME
#     before_install.sh - Prepare the environment.
#
# SYNOPSIS
#     before_install.sh
#
# DESCRIPTION
#     Configures the Travis CI environment, installs ORCA, and prepares the SUT.

cd "$(dirname "$0")"; source _includes.sh

# Disable Xdebug.
phpenv config-rm xdebug.ini

# Remove PHP memory limit.
echo 'memory_limit = -1' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

# Prevent email errors.
echo 'sendmail_path = /bin/true' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

# Prevent PHPStan warnings about APCu constants.
echo 'extension = apcu.so' >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

# Install the PECL YAML parser for strict YAML parsing.
yes | pecl install yaml

# Install Composer optimizations for faster builds.
composer global require \
  hirak/prestissimo \
  zaporylie/composer-drupal-optimizations

# Make Composer Patches throw an error when it can't apply a patch.
export COMPOSER_EXIT_ON_PATCH_FAILURE=1

# Install ORCA.
composer -d${ORCA_ROOT} install

# Ensure the checked out branch is named after the nearest Git version branch.
git -C "${TRAVIS_BUILD_DIR}" rev-parse --abbrev-ref HEAD
if [[ $(git -C "${TRAVIS_BUILD_DIR}" rev-parse --abbrev-ref HEAD) != "$ORCA_SUT_BRANCH" ]]; then
  git -C "${TRAVIS_BUILD_DIR}" branch -f "$ORCA_SUT_BRANCH"
  git -C "${TRAVIS_BUILD_DIR}" checkout "$ORCA_SUT_BRANCH"
fi
