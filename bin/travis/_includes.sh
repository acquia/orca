#!/usr/bin/env bash

# NAME
#     _includes.sh - Include reusable code.
#
# SYNOPSIS
#     cd "$(dirname "$0")"; source _includes.sh
#
# DESCRIPTION
#     Includes common features used by the Travis CI scripts.

# Outputs a formatted error message and exit with an error code.
function error {
  RED="\033[1;31m"
  NO_COLOR="\033[0m"
  echo -e "\n${RED}$@${NO_COLOR}\n"
  exit 1
}

# Prevent CI scripts from being run locally.
[[ "$TRAVIS" ]] || error "Error: This script is meant to run on Travis CI only."

# Assert required environment variables.
[[ "$ORCA_SUT_NAME" ]] || error "Error: Missing required ORCA_SUT_NAME environment variable.\nHint: ORCA_SUT_NAME=drupal/example"
[[ "$ORCA_SUT_BRANCH" ]] || error "Error: Missing required ORCA_SUT_BRANCH environment variable.\nHint: ORCA_SUT_BRANCH=8.x-1.x"

# Set environment variables.
export ORCA_ROOT="$(cd "$(dirname "$BASH_SOURCE")/../.." && pwd)"
export ORCA_FIXTURE_DIR=${ORCA_FIXTURE_DIR:="${ORCA_ROOT}/../orca-build"}

# Exit as soon as one command returns a non-zero exit code and make the shell
# print all lines in the script before executing them.
# @see https://docs.travis-ci.com/user/job-lifecycle/#complex-build-commands
set -ev
