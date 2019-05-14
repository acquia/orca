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

[[ "$ORCA_JOB" != "DEPRECATED_CODE_SCAN_SUT" ]] || orca fixture:init -f --sut=${ORCA_SUT_NAME} --sut-only --no-site-install

[[ "$ORCA_JOB" != "DEPRECATED_CODE_SCAN_CONTRIB" ]] || orca fixture:init -f --no-site-install

[[ "$ORCA_JOB" != "ISOLATED_RECOMMENDED" ]] || orca fixture:init -f --sut=${ORCA_SUT_NAME} --sut-only --core=CURRENT_RECOMMENDED

[[ "$ORCA_JOB" != "INTEGRATED_RECOMMENDED" ]] || orca fixture:init -f --sut=${ORCA_SUT_NAME} --core=CURRENT_RECOMMENDED

[[ "$ORCA_JOB" != "CORE_PREVIOUS" ]] || orca fixture:init -f --sut=${ORCA_SUT_NAME} --core=PREVIOUS_RELEASE

[[ "$ORCA_JOB" != "ISOLATED_DEV" ]] || orca fixture:init -f --sut=${ORCA_SUT_NAME} --sut-only --core=CURRENT_DEV --dev

[[ "$ORCA_JOB" != "INTEGRATED_DEV" ]] || orca fixture:init -f --sut=${ORCA_SUT_NAME} --core=CURRENT_DEV --dev

[[ "$ORCA_JOB" != "CORE_NEXT" ]] || orca fixture:init -f --sut=${ORCA_SUT_NAME} --core=NEXT_DEV --dev

[[ "$ORCA_JOB" != "CUSTOM" ]] || orca fixture:init -f --sut=${ORCA_SUT_NAME} ${ORCA_CUSTOM_FIXTURE_INIT_ARGS:=}
