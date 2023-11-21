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



if [[ "$ORCA_ENABLE_NIGHTWATCH" == "TRUE" && "$ORCA_SUT_HAS_NIGHTWATCH_TESTS" && -d "$ORCA_YARN_DIR" ]]; then
  (
    cd "$ORCA_YARN_DIR" || exit
    orca fixture:run-server &
    SERVER_PID=$!

    # @todo could we set DRUPAL_TEST_CHROMEDRIVER_AUTOSTART instead of launching Chromedriver manually?
    chromedriver --disable-dev-shm-usage --disable-extensions --disable-gpu --headless --no-sandbox --port=4444 &
    CHROMEDRIVER_PID=$!

    eval "yarn test:nightwatch \\
      --headless \\
      --passWithNoTests \\
      --tag=$ORCA_SUT_MACHINE_NAME"

    kill -0 $SERVER_PID
    kill -0 $CHROMEDRIVER_PID
  )
fi
echo $?
if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB script $ORCA_SUT_NAME"
  echo $?
fi

