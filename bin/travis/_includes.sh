#!/usr/bin/env bash

# NAME
#     _includes.sh - Include reusable code.
#
# SYNOPSIS
#     cd "$(dirname "$0")"; source _includes.sh
#
# DESCRIPTION
#     Includes common features used by the Travis CI scripts.

# Outputs a formatted error message and exits with an error code if a given
# condition is not met.
function assert {
  if [[ ! "$1" ]]; then
    RED="\033[1;31m"
    NO_COLOR="\033[0m"
    echo -e "\n${RED}Error: $2${NO_COLOR}\n"
    exit 1
  fi
}

# Asserts that necessary environment variables are set.
function assert_env_vars {
  assert "$ORCA_SUT_NAME" "Missing required ORCA_SUT_NAME environment variable.\nHint: ORCA_SUT_NAME=drupal/example"
  assert "$ORCA_SUT_BRANCH" "Missing required ORCA_SUT_BRANCH environment variable.\nHint: ORCA_SUT_BRANCH=8.x-1.x"
}

# Prevent CI scripts from being run locally.
assert "$TRAVIS" "This script is meant to run on Travis CI only."

# Set environment variables.
export ORCA_ROOT="$(cd "$(dirname "$BASH_SOURCE")/../.." && pwd)"
export ORCA_FIXTURE_DIR=${ORCA_FIXTURE_DIR:="${ORCA_ROOT}/../orca-build"}
export ORCA_FIXTURE_DOCROOT=${ORCA_FIXTURE_DIR}/docroot
export ORCA_SUT_DIR=${ORCA_SUT_DIR:=${TRAVIS_BUILD_DIR}}

# Add binary directories to PATH.
export PATH="$HOME/.composer/vendor/bin:$PATH"
export PATH="$ORCA_ROOT/bin:$PATH"
export PATH="$ORCA_ROOT/vendor/bin:$PATH"
export PATH="$ORCA_FIXTURE_DIR/vendor/bin:$PATH"
export PATH="$TRAVIS_BUILD_DIR/vendor/bin:$PATH"

# Add convenient aliases.
alias drush="drush -r ${ORCA_FIXTURE_DOCROOT}"

# Exit as soon as one command returns a non-zero exit code and make the shell
# print all lines in the script before executing them.
# @see https://docs.travis-ci.com/user/job-lifecycle/#complex-build-commands
set -ev
