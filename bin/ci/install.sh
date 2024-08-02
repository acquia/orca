#!/usr/bin/env bash

# NAME
#     install.sh - Create the test fixture.
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture and places the SUT.

cd "$(dirname "$0")" || exit; source _includes.sh

if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB install $ORCA_SUT_NAME"
fi

if [[ "$ORCA_ENABLE_NIGHTWATCH" == "TRUE" && "$ORCA_SUT_HAS_NIGHTWATCH_TESTS" && -d "$ORCA_YARN_DIR" ]]; then
  (
    eval 'cd "$ORCA_YARN_DIR" || exit'
    # Install yarn 4.1.1.
    eval 'npm cache clean --force'
    # Remove the previous yarn installed in the container.
    eval 'npm uninstall -g yarn'
    eval 'npm install -g corepack'
    eval 'corepack enable'
    eval 'yarn set version 4.1.1'
    eval 'yarn -v'
    eval "yarn install"
  )
fi
