#!/usr/bin/env bash

# NAME
#     after_script.sh - Perform final, post-script tasks.
#
# SYNOPSIS
#     after_script.sh
#
# DESCRIPTION
#     Simulates sending test coverage and telemetry.

cd "$(dirname "$0")" || exit; source ../_includes.sh

cd ../../../; pwd

# Simulate sending test coverage data to Coveralls (coveralls.io).
if [[ "$ORCA_JOB" == "ISOLATED_RECOMMENDED_COVERAGE" ]]; then
  # shellcheck disable=SC1004
  eval 'php-coveralls -vv \
    --coverage_clover="$ORCA_SELF_TEST_COVERAGE" \
    --json_path="${TMPDIR:-/tmp}/coveralls.json"'
else
  notice "No coverage data sent to Coveralls."
fi

# Simulate telemetry.
orca internal:log-job --simulate
