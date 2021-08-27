#!/usr/bin/env bash

# NAME
#     after_success.sh - Perform post-success tasks.
#
# SYNOPSIS
#     after_success.sh
#
# DESCRIPTION
#     Conditionally sends code coverage data to Coveralls.

cd "$(dirname "$0")" || exit 1; source ../_includes.sh

cd ../../../ || exit 1

# Send test coverage data to Coveralls (coveralls.io).
if [[ "$ORCA_COVERAGE_ENABLE" == TRUE && "$ORCA_COVERALLS_ENABLE" == TRUE ]]; then
  # shellcheck disable=SC1004
  eval 'php-coveralls -vv \
    --coverage_clover="$ORCA_SELF_TEST_COVERAGE_CLOVER" \
    --json_path="${TMPDIR:-/tmp}/coveralls.json"'
else
  notice "No coverage data sent to Coveralls."
fi
