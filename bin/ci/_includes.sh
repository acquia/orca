#!/usr/bin/env bash

# NAME
#     _includes.sh - Include reusable code.
#
# SYNOPSIS
#     cd "$(dirname "$0")" || exit; source _includes.sh
#
# DESCRIPTION
#     Includes common features used by ORCA scripts.

# Exit as soon as one command returns a non-zero exit code.
set -e

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

# Outputs a formatted warning message.
function notice {
  CYAN="\e[34m"
  NO_COLOR="\033[0m"
  printf "\n%bNotice: %b%b\n" "$CYAN" "$1" "$NO_COLOR"
}

# Assert that necessary environment variables are set.
if [[ "$ORCA_JOB" ]]; then
  assert "$ORCA_SUT_NAME" "Missing required ORCA_SUT_NAME environment variable.\nHint: ORCA_SUT_NAME=drupal/example"
  if [[ "$CI" ]]; then assert "$ORCA_SUT_BRANCH" "Missing required ORCA_SUT_BRANCH environment variable.\nHint: ORCA_SUT_BRANCH=8.x-1.x"; fi
fi
if [[ ! "$CI" && "$ORCA_JOB" = "STATIC_CODE_ANALYSIS" ]]; then
  assert "$ORCA_SUT_DIR" "Missing required ORCA_SUT_DIR environment variable.\nHint: ORCA_SUT_DIR=~/Projects/example"
fi

# Set working directory
if [[ "$GITHUB_WORKSPACE" ]]; then
  CI_WORKSPACE="$GITHUB_WORKSPACE"
fi

# Set event type
if [[ "$GITHUB_EVENT_NAME" = "schedule" ]]; then
  export CI_EVENT="cron"
fi

# Set environment variables.
ORCA_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
export ORCA_ROOT
export ORCA_COVERAGE_CLOVER=${ORCA_COVERAGE_CLOVER:="$HOME/build/logs/clover.xml"}
export ORCA_COVERAGE_ENABLE=${ORCA_COVERAGE_ENABLE:="FALSE"}
export ORCA_FIXTURE_DIR=${ORCA_FIXTURE_DIR:="$ORCA_ROOT/../orca-build"}
export ORCA_FIXTURE_PROFILE=${ORCA_FIXTURE_PROFILE:="orca"}
export ORCA_JUNIT_LOG=${ORCA_JUNIT_LOG:="$HOME/build/logs/junitLog.xml"}
export ORCA_SUT_DIR=${ORCA_SUT_DIR:=${CI_WORKSPACE}}
ORCA_SUT_HAS_NIGHTWATCH_TESTS=$(cd "$ORCA_SUT_DIR"; find . -regex ".*/Nightwatch/.*" -name \*.js)
export ORCA_SUT_HAS_NIGHTWATCH_TESTS
export ORCA_SUT_MACHINE_NAME=${ORCA_SUT_NAME##*\/}
export ORCA_TELEMETRY_ENABLE=${ORCA_TELEMETRY_ENABLE:="FALSE"}
export ORCA_AMPLITUDE_USER_ID=${ORCA_AMPLITUDE_USER_ID:="$ORCA_SUT_NAME:$ORCA_SUT_BRANCH"}
export ORCA_ENABLE_NIGHTWATCH=${ORCA_ENABLE_NIGHTWATCH:="FALSE"}
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

if [[ ! "$ORCA_TEMP_DIR" ]]; then
  # GitHub Actions.
  if [[ "$RUNNER_TEMP" ]]; then
    export ORCA_TEMP_DIR="$RUNNER_TEMP"
  # Fallback default.
  else
    export ORCA_TEMP_DIR="/tmp"
  fi
fi

# Override the available columns setting to prevent Drush output from wrapping
# too narrowly.
export COLUMNS=125

# Add binary directories to PATH.
export PATH="$HOME/.composer/vendor/bin:$PATH"
export PATH="$ORCA_ROOT/bin:$PATH"
export PATH="$ORCA_ROOT/vendor/bin:$PATH"
export PATH="$ORCA_FIXTURE_DIR/vendor/bin:$PATH"
export PATH="$CI_WORKSPACE/vendor/bin:$PATH"
# Put this last to ensure that the host's Composer is preferred.
export PATH="$HOME/.phpenv/shims/:$PATH"
export PATH="/usr/bin/:$PATH"
export PATH="/usr/local/bin/:$PATH"

# Add convenient aliases.
alias drush='drush -r "$ORCA_FIXTURE_DIR"'

# Commands exiting with a non-zero status prior to this point constitute an
# error, i.e. an ORCA configuration problem, and always stop execution.
# Commands exiting with a non-zero status after this point constitute a failure,
# i.e. a problem with the SUT or test fixture, and may or may not stop execution
# depending on whether the job is allowed to fail.

allowed_failures=(
  "INTEGRATED_TEST_ON_NEXT_MINOR_DEV"
  "DEPRECATED_CODE_SCAN_W_CONTRIB"
  "ISOLATED_TEST_ON_NEXT_MINOR_DEV"
  "INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR_DEV"
  "LOOSE_DEPRECATED_CODE_SCAN"
  "ISOLATED_UPGRADE_TEST_TO_NEXT_MAJOR_DEV"
  "ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV"
  "INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV"
  "INTEGRATED_TEST_ON_CURRENT_DEV"
  "ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER"
  "INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER"
)
if [[ " ${allowed_failures[*]} " =~ " ${ORCA_JOB} " ]]; then
  set +e
  notice "This job is allowed to fail and will report as passing regardless of outcome."
fi

# Make the shell print all lines in the script before executing them.
set -v


