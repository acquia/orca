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

[[ "$ORCA_FIXTURE_PROFILE" = "minimal" ]] || SUT_ONLY="--sut-only"

case "$ORCA_JOB" in
  "STATIC_CODE_ANALYSIS") eval "orca static-analysis:run $ORCA_SUT_DIR" ;;
  "DEPRECATED_CODE_SCAN_SUT") eval "orca deprecated-code-scan:run --sut=$ORCA_SUT_NAME" ;;
  "DEPRECATED_CODE_SCAN_CONTRIB") eval "orca deprecated-code-scan:run --contrib" ;;
  "ISOLATED_RECOMMENDED") eval "orca tests:run --sut=$ORCA_SUT_NAME --sut-only" ;;
  "INTEGRATED_RECOMMENDED") eval "orca tests:run --sut=$ORCA_SUT_NAME $SUT_ONLY" ;;
  "CORE_PREVIOUS") eval "orca tests:run --sut=$ORCA_SUT_NAME $SUT_ONLY" ;;
  "ISOLATED_DEV") eval "orca tests:run --sut=$ORCA_SUT_NAME --sut-only" ;;
  "INTEGRATED_DEV") eval "orca tests:run --sut=$ORCA_SUT_NAME $SUT_ONLY" ;;
  "CORE_NEXT") eval "orca tests:run --sut=$ORCA_SUT_NAME $SUT_ONLY" ;;
  "CUSTOM") eval "orca tests:run --sut=$ORCA_SUT_NAME ${ORCA_CUSTOM_TESTS_RUN_ARGS:=}" ;;
esac
