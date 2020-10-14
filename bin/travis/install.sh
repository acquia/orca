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

if [[ "$ORCA_ENABLE_NIGHTWATCH" = "TRUE" && "$ORCA_SUT_HAS_NIGHTWATCH_TESTS" && -d "$ORCA_YARN_DIR" ]]; then
  (
    cd "$ORCA_YARN_DIR" || exit
    eval "yarn install"
  )
fi
