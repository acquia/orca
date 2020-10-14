#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA tests.
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests.

cd "$(dirname "$0")" || exit; source _includes.sh

[[ ! -d "$ORCA_FIXTURE_DIR" ]] || orca fixture:status

if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB script $ORCA_SUT_NAME"
fi

if [[ "$ORCA_ENABLE_NIGHTWATCH" = "TRUE" && "$ORCA_SUT_HAS_NIGHTWATCH_TESTS" && -d "$ORCA_YARN_DIR" ]]; then
  (
    cd "$ORCA_YARN_DIR" || exit
    orca fixture:run-server &
    PID=$!

    eval "yarn test:nightwatch \\
      --headless \\
      --passWithNoTests \\
      --tag=$ORCA_SUT_MACHINE_NAME"

    kill -0 $PID
  )
fi
