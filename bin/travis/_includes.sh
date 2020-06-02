#!/usr/bin/env bash

# NAME
#     _includes.sh - Include reusable code.
#
# SYNOPSIS
#     cd "$(dirname "$0")" || exit; source _includes.sh
#
# DESCRIPTION
#     Includes common features used by the Travis CI scripts.

# Outputs a formatted error message and exits with an error code if a given
# condition is not met.
function assert {
  if [[ ! "$1" ]]; then
    RED="\033[1;31m"
    NO_COLOR="\033[0m"
    printf "\n%bError: %b%b\n" "$RED" "$2" "$NO_COLOR"
    exit 1
  fi
}

# Assert that necessary environment variables are set.
if [[ "$ORCA_JOB" != "DEPRECATED_CODE_SCAN_CONTRIB" ]]; then
  assert "$ORCA_SUT_NAME" "Missing required ORCA_SUT_NAME environment variable.\nHint: ORCA_SUT_NAME=drupal/example"
  if [[ "$TRAVIS" ]]; then assert "$ORCA_SUT_BRANCH" "Missing required ORCA_SUT_BRANCH environment variable.\nHint: ORCA_SUT_BRANCH=8.x-1.x"; fi
fi
if [[ ! "$TRAVIS" && "$ORCA_JOB" = "STATIC_CODE_ANALYSIS" ]]; then assert "$ORCA_SUT_DIR" "Missing required ORCA_SUT_DIR environment variable.\nHint: ORCA_SUT_DIR=~/Projects/example"; fi

# Set environment variables.
ORCA_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
export ORCA_ROOT
export ORCA_FIXTURE_DIR=${ORCA_FIXTURE_DIR:="$ORCA_ROOT/../orca-build"}
export ORCA_FIXTURE_PROFILE=${ORCA_FIXTURE_PROFILE:="orca"}
DEFAULT_PROJECT_TEMPLATE=$([[ "$ORCA_JOB" == "D9_READINESS" ]] && echo "acquia/drupal-recommended-project" || echo "acquia/blt-project")
export ORCA_FIXTURE_PROJECT_TEMPLATE=${ORCA_FIXTURE_PROJECT_TEMPLATE:="$DEFAULT_PROJECT_TEMPLATE"}
export ORCA_SUT_DIR=${ORCA_SUT_DIR:=${TRAVIS_BUILD_DIR}}
ORCA_SUT_HAS_NIGHTWATCH_TESTS=$(cd "$ORCA_SUT_DIR"; find . -regex ".*/Nightwatch/.*" -name \*.js)
export ORCA_SUT_HAS_NIGHTWATCH_TESTS
export ORCA_SUT_MACHINE_NAME=${ORCA_SUT_NAME##*\/}
export ORCA_TELEMETRY_ENABLE=${ORCA_TELEMETRY_ENABLE:="FALSE"}
export ORCA_AMPLITUDE_USER_ID=${ORCA_AMPLITUDE_USER_ID:="$ORCA_SUT_NAME:$ORCA_SUT_BRANCH"}
export ORCA_ENABLE_NIGHTWATCH=${ORCA_ENABLE_NIGHTWATCH:="TRUE"}
export ORCA_YARN_DIR="${ORCA_FIXTURE_DIR}/docroot/core"
export DRUPAL_NIGHTWATCH_IGNORE_DIRECTORIES="node_modules,vendor,.*,sites/*/files,sites/*/private,sites/simpletest"
export DRUPAL_NIGHTWATCH_OUTPUT="sites/default/reports/nightwatch"
export DRUPAL_NIGHTWATCH_SEARCH_DIRECTORY="../"
export DRUPAL_TEST_BASE_URL="http://localhost:8080"
export DRUPAL_TEST_CHROMEDRIVER_AUTOSTART="false"
export DRUPAL_TEST_DB_URL="sqlite://localhost/sites/default/files/db.sqlite"
export DRUPAL_TEST_WEBDRIVER_CHROME_ARGS="--disable-gpu --headless --no-sandbox"
export DRUPAL_TEST_WEBDRIVER_HOSTNAME="localhost"
export DRUPAL_TEST_WEBDRIVER_PORT="4444"

# Override the available columns setting to prevent Drush output from wrapping
# too narrowly.
export COLUMNS=125

# Add binary directories to PATH.
export PATH="$HOME/.composer/vendor/bin:$PATH"
export PATH="$ORCA_ROOT/bin:$PATH"
export PATH="$ORCA_ROOT/vendor/bin:$PATH"
export PATH="$ORCA_FIXTURE_DIR/vendor/bin:$PATH"
export PATH="$TRAVIS_BUILD_DIR/vendor/bin:$PATH"

# Add convenient aliases.
alias drush='drush -r "$ORCA_FIXTURE_DIR"'

# Exit as soon as one command returns a non-zero exit code and make the shell
# print all lines in the script before executing them.
# @see https://docs.travis-ci.com/user/job-lifecycle/#complex-build-commands
set -ev
