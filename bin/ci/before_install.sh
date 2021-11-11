#!/usr/bin/env bash

# NAME
#     before_install.sh - Prepare the environment.
#
# SYNOPSIS
#     before_install.sh
#
# DESCRIPTION
#     Configures the CI environment, installs ORCA, and prepares the SUT.

cd "$(dirname "$0")" || exit; source _includes.sh

# The remaining before_install commands should only be run on CI.
[[ "$CI" ]] || exit 0

# Display the Google Chrome version.
google-chrome-stable --version

# Display the Node version.
echo "$TRAVIS_NODE_VERSION"

# Display the Yarn version.
yarn --version

# Disable Xdebug except on code coverage jobs.
if [[ ! "$ORCA_COVERAGE_ENABLE" == TRUE ]]; then
  if [[ "$TRAVIS" ]]; then phpenv config-rm xdebug.ini; fi
  if [[ "$GITHUB_ACTIONS" ]]; then
    # phpdismod would be simpler but flaky
    # @see https://github.com/shivammathur/setup-php/issues/350#issuecomment-735370872
    scan_dir=$(php --ini | grep additional | sed -e "s|.*: s*||")
    ini_file=$(php --ini | grep "Loaded Configuration" | sed -e "s|.*:s*||" | sed "s/ //g")
    pecl_file="$scan_dir"/99-pecl.ini
    sudo sed -Ei "/xdebug/d" "${ini_file:?}"
    sudo sed -Ei "/xdebug/d" "${pecl_file:?}"
    sudo rm -rf "$scan_dir"/*xdebug*
  fi
fi

# Travis CI installs YAML from PECL, but it's already present in GitHub Actions.
if [[ "$TRAVIS" ]]; then
  {
    # Remove PHP memory limit.
    echo 'memory_limit = -1'
    # Prevent email errors.
    echo 'sendmail_path = /bin/true'
    # Prevent PHPStan warnings about APCu constants.
    echo 'extension = apcu.so'
  } >> "$HOME/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini"

  # Install the PECL YAML parser for strict YAML parsing.
  yes | pecl install yaml
fi

# Display PHP information.
php -i

# Download and install ORCA libraries if necessary. This provides compatibility
# with the old method of installing ORCA via `git clone` rather than the newer
# `composer create-project` approach.
[[ -d "$ORCA_ROOT/vendor" ]] || composer -d"$ORCA_ROOT" install

# Display ORCA version and configuration values.
orca --version
orca debug:env-vars

# Silence the "You are in 'detached HEAD' state" warning from Git.
git config --global advice.detachedHead false

# Ensure the checked out branch is named after the nearest Git version branch.
git -C "$ORCA_SUT_DIR" rev-parse --abbrev-ref HEAD
if [[ $(git -C "$ORCA_SUT_DIR" rev-parse --abbrev-ref HEAD) != "$ORCA_SUT_BRANCH" ]]; then
  git -C "$ORCA_SUT_DIR" branch -f "$ORCA_SUT_BRANCH"
  git -C "$ORCA_SUT_DIR" checkout "$ORCA_SUT_BRANCH"
fi

if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB before_install $ORCA_SUT_NAME"
fi
