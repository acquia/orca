#!/usr/bin/env bash

# NAME
#     install.sh - Install Travis CI dependencies.
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture.

cd "$(dirname "$0")"; source _includes.sh

assert_env_vars

case "$ORCA_JOB" in
  "DEPRECATED_CODE_SCAN_SUT") eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --sut-only --no-site-install" ;;
  "DEPRECATED_CODE_SCAN_CONTRIB") eval "orca fixture:init -f --no-site-install" ;;
  "ISOLATED_RECOMMENDED") eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --sut-only --core=CURRENT_RECOMMENDED --profile=$ORCA_FIXTURE_PROFILE" ;;
  "INTEGRATED_RECOMMENDED") eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --core=CURRENT_RECOMMENDED --profile=$ORCA_FIXTURE_PROFILE" ;;
  "CORE_PREVIOUS") eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --core=PREVIOUS_RELEASE --profile=$ORCA_FIXTURE_PROFILE" ;;
  "ISOLATED_DEV") eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --sut-only --core=CURRENT_DEV --dev --profile=$ORCA_FIXTURE_PROFILE" ;;
  "INTEGRATED_DEV") eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --core=CURRENT_DEV --dev --profile=$ORCA_FIXTURE_PROFILE" ;;
  "CORE_NEXT") eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --core=NEXT_DEV --dev --profile=$ORCA_FIXTURE_PROFILE" ;;
  "CUSTOM") eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --profile=$ORCA_FIXTURE_PROFILE ${ORCA_CUSTOM_FIXTURE_INIT_ARGS:=}" ;;
esac
