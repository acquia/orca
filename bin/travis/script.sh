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

assert_env_vars

[[ ! -d "$ORCA_FIXTURE_DIR" ]] || orca fixture:status

# The Drupal installation profile is such a fundamental aspect of the fixture
# that it cannot be changed and other packages' tests still be expected to pass.
# Thus if the SUT changes it, only its own tests are run.
[[ "$ORCA_FIXTURE_PROFILE" = "orca" ]] || SUT_ONLY="--sut-only"

case "$ORCA_JOB" in
  "STATIC_CODE_ANALYSIS") eval "orca qa:static-analysis $ORCA_SUT_DIR"; unset ORCA_ENABLE_NIGHTWATCH ;;
  "DEPRECATED_CODE_SCAN") eval "orca qa:deprecated-code-scan --sut=$ORCA_SUT_NAME"; unset ORCA_ENABLE_NIGHTWATCH ;;
  "DEPRECATED_CODE_SCAN_CONTRIB") eval "orca qa:deprecated-code-scan --contrib"; unset ORCA_ENABLE_NIGHTWATCH ;;
  "ISOLATED_RECOMMENDED") eval "orca qa:automated-tests --sut=$ORCA_SUT_NAME --sut-only" ;;
  "INTEGRATED_RECOMMENDED") eval "orca qa:automated-tests --sut=$ORCA_SUT_NAME $SUT_ONLY" ;;
  "CORE_PREVIOUS") eval "orca qa:automated-tests --sut=$ORCA_SUT_NAME $SUT_ONLY" ;;
  "ISOLATED_DEV") eval "orca qa:automated-tests --sut=$ORCA_SUT_NAME --sut-only" ;;
  "INTEGRATED_DEV") eval "orca qa:automated-tests --sut=$ORCA_SUT_NAME $SUT_ONLY" ;;
  "CORE_NEXT") eval "orca qa:automated-tests --sut=$ORCA_SUT_NAME $SUT_ONLY" ;;
  "D9_READINESS") eval "orca qa:automated-tests --sut=$ORCA_SUT_NAME --sut-only" ;;
  "CUSTOM") eval "orca qa:automated-tests --sut=$ORCA_SUT_NAME ${ORCA_CUSTOM_TESTS_RUN_ARGS:=}" ;;
esac

if [[ "$ORCA_ENABLE_NIGHTWATCH" && "$ORCA_SUT_HAS_NIGHTWATCH_TESTS" && -d "$ORCA_YARN_DIR" ]]; then
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
