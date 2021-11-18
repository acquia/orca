#!/usr/bin/env bash

# NAME
#     after_success.sh - Perform post-success tasks.
#
# SYNOPSIS
#     after_success.sh
#
# DESCRIPTION
#     Conditionally sends code coverage data to Coveralls.

cd "$(dirname "$0")" || exit 1; source _includes.sh

if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB after_success $ORCA_SUT_NAME"
fi

# Send test coverage data to Coveralls (coveralls.io) if enabled.
if [[ "$ORCA_COVERAGE_ENABLE" == TRUE && "$ORCA_COVERALLS_ENABLE" == TRUE ]]; then
  (
    cd "$ORCA_SUT_DIR" || exit 1
    # shellcheck disable=SC1004
    eval 'php-coveralls -vv \
      --coverage_clover="$ORCA_COVERAGE_CLOVER" \
      --json_path="$ORCA_TEMP_DIR/coveralls.json" \
      --root_dir="$ORCA_SUT_DIR"'
  )
else
  notice "No coverage data sent to Coveralls."
fi
