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

case "$ORCA_JOB" in
  "STATIC_CODE_ANALYSIS") unset ORCA_ENABLE_NIGHTWATCH ;;
  "DEPRECATED_CODE_SCAN") orca debug:packages; eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --sut-only --no-site-install"; unset ORCA_ENABLE_NIGHTWATCH ;;
  "DEPRECATED_CODE_SCAN_CONTRIB") orca debug:packages; eval "orca fixture:init -f --no-site-install"; unset ORCA_ENABLE_NIGHTWATCH ;;
  "ISOLATED_RECOMMENDED") orca debug:packages CURRENT_RECOMMENDED; eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --sut-only --core=CURRENT_RECOMMENDED --profile=$ORCA_FIXTURE_PROFILE --project-template=$ORCA_FIXTURE_PROJECT_TEMPLATE" ;;
  "INTEGRATED_RECOMMENDED") orca debug:packages CURRENT_RECOMMENDED; eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --core=CURRENT_RECOMMENDED --profile=$ORCA_FIXTURE_PROFILE --project-template=$ORCA_FIXTURE_PROJECT_TEMPLATE" ;;
  "CORE_PREVIOUS") orca debug:packages PREVIOUS_RELEASE; eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --core=PREVIOUS_RELEASE --profile=$ORCA_FIXTURE_PROFILE --project-template=$ORCA_FIXTURE_PROJECT_TEMPLATE" ;;
  "ISOLATED_DEV") orca debug:packages CURRENT_DEV; eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --sut-only --core=CURRENT_DEV --dev --profile=$ORCA_FIXTURE_PROFILE --project-template=$ORCA_FIXTURE_PROJECT_TEMPLATE" ;;
  "INTEGRATED_DEV") orca debug:packages CURRENT_DEV; eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --core=CURRENT_DEV --dev --profile=$ORCA_FIXTURE_PROFILE --project-template=$ORCA_FIXTURE_PROJECT_TEMPLATE" ;;
  "CORE_NEXT") orca debug:packages NEXT_DEV; eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --core=NEXT_DEV --dev --profile=$ORCA_FIXTURE_PROFILE --project-template=$ORCA_FIXTURE_PROJECT_TEMPLATE" ;;
  "D9_READINESS") orca debug:packages D9_READINESS; eval "orca fixture:init -f --sut=$ORCA_SUT_NAME --sut-only --core=D9_READINESS --dev --profile=$ORCA_FIXTURE_PROFILE --project-template=$ORCA_FIXTURE_PROJECT_TEMPLATE" ;;
esac

if [[ "$ORCA_ENABLE_NIGHTWATCH" = "TRUE" && "$ORCA_SUT_HAS_NIGHTWATCH_TESTS" && -d "$ORCA_YARN_DIR" ]]; then
  (
    cd "$ORCA_YARN_DIR" || exit
    eval "yarn install"
  )
fi

eval "orca ci:run $ORCA_JOB install $ORCA_SUT_NAME"
