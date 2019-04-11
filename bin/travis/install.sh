#!/usr/bin/env bash

# NAME
#     install.sh - Install Travis CI dependencies
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture.

cd "$(dirname "$0")"; source _includes.sh

assert_env_vars

[[ "$ORCA_JOB" != "DEPRECATED_CODE_SCAN_SUT" ]] || ../orca fixture:init -f --sut=${ORCA_SUT_NAME} --sut-only

[[ "$ORCA_JOB" != "DEPRECATED_CODE_SCAN_CONTRIB" ]] || ../orca fixture:init -f --sut=${ORCA_SUT_NAME} --sut-only

[[ "$ORCA_JOB" != "ISOLATED_RECOMMENDED" ]] || ../orca fixture:init -f --sut=${ORCA_SUT_NAME} --sut-only

[[ "$ORCA_JOB" != "INTEGRATED_RECOMMENDED" ]] || ../orca fixture:init -f --sut=${ORCA_SUT_NAME}

[[ "$ORCA_JOB" != "ISOLATED_DEV" ]] || ../orca fixture:init -f --sut=${ORCA_SUT_NAME} --sut-only --dev

[[ "$ORCA_JOB" != "INTEGRATED_DEV" ]] || ../orca fixture:init -f --sut=${ORCA_SUT_NAME} --dev
