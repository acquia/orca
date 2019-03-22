#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA tests
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests.

cd "$(dirname "$0")"; source _includes.sh

[[ "$ORCA_JOB" != "STATIC_CODE_ANALYSIS" ]] || ../orca static-analysis:run "${TRAVIS_BUILD_DIR}"

[[ "$ORCA_JOB" != "DEPRECATED_CODE_SCAN_SUT" ]] || ../orca deprecated-code-scan:run --sut=${ORCA_SUT_NAME}

[[ "$ORCA_JOB" != "DEPRECATED_CODE_SCAN_CONTRIB" ]] || ../orca deprecated-code-scan:run --contrib

[[ "$ORCA_JOB" != "ISOLATED_RECOMMENDED" ]] || ../orca tests:run --sut=${ORCA_SUT_NAME} --sut-only

[[ "$ORCA_JOB" != "INTEGRATED_RECOMMENDED" ]] || ../orca tests:run --sut=${ORCA_SUT_NAME}

[[ "$ORCA_JOB" != "ISOLATED_DEV" ]] || ../orca tests:run --sut=${ORCA_SUT_NAME} --sut-only

[[ "$ORCA_JOB" != "INTEGRATED_DEV" ]] || ../orca tests:run --sut=${ORCA_SUT_NAME}
