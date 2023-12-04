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
#google-chrome-stable --version

# Display the Yarn version.
yarn --version

# Disable Xdebug except on code coverage jobs.
if [[ ! "$ORCA_COVERAGE_ENABLE" == TRUE ]]; then
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

if [[ "$JENKINS_HOME" ]]; then
  if  grep -Fxq "Alpine" /etc/os-release
  then
     echo "Alpine Linux";
  else
     # Install ChromeDriver.
     # @see https://chromedriver.chromium.org/downloads/version-selection
     # @see https://groups.google.com/g/chromedriver-users/c/clpipqvOGjE/m/5NxzS_SRAgAJ
     # Get Google Chrome version.
     CHROMEDRIVER="$( google-chrome-stable --version)"
     echo "$CHROMEDRIVER"
     CHROMEDRIVER_VERSION="$(echo "$CHROMEDRIVER" | awk '{print $3}')"
     echo "CHROMEDRIVER_VERSION=$CHROMEDRIVER_VERSION"
     # Cut off last part from google chrome version.
     CHROMEDRIVER_VERSION_FAMILY="$(echo "$CHROMEDRIVER_VERSION" | awk -F'.' '{print $1,$2,$3}' OFS='.' )"
     MAJOR="$(echo "$CHROMEDRIVER_VERSION_FAMILY" | awk -F'.' '{print $1}' )"
     if (( $MAJOR < 115 ))
     then
       echo "VERSION_FAMILY=$CHROMEDRIVER_VERSION_FAMILY"
       # check latest_release
       VERSION=$(curl -f --silent https://chromedriver.storage.googleapis.com/LATEST_RELEASE_${CHROMEDRIVER_VERSION_FAMILY})
       echo "FINAL_VERSION=$VERSION"
       # Download driver
       wget -N https://chromedriver.storage.googleapis.com/${VERSION}/chromedriver_linux64.zip -P ~/
       unzip ~/chromedriver_linux64.zip -d ~/
       rm ~/chromedriver_linux64.zip
       mv -f ~/chromedriver /usr/local/share/
       chmod +x /usr/local/share/chromedriver
       ln -s /usr/local/share/chromedriver /usr/local/bin/chromedriver
     else
       # Download driver
       wget -N https://edgedl.me.gvt1.com/edgedl/chrome/chrome-for-testing/${CHROMEDRIVER_VERSION}/linux64/chromedriver-linux64.zip -P ~/
       unzip ~/chromedriver-linux64.zip -d ~/
       rm ~/chromedriver-linux64.zip
       mv -f ~/chromedriver-linux64/chromedriver /usr/local/share/
       chmod +x /usr/local/share/chromedriver
       ln -s /usr/local/share/chromedriver /usr/local/bin/chromedriver
     fi
  fi
fi






# Display PHP information.
which php
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
  git -C "$ORCA_SUT_DIR" branch -f "$ORCA_SUT_BRANCH" HEAD
  git -C "$ORCA_SUT_DIR" checkout "$ORCA_SUT_BRANCH"
fi

if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB before_install $ORCA_SUT_NAME"
fi
