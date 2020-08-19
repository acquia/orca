#!/usr/bin/env bash

# NAME
#     after_script.sh - Perform final, post-script tasks.
#
# SYNOPSIS
#     after_script.sh
#
# DESCRIPTION
#     Conditionally logs the job and displays upstream ORCA status.

cd "$(dirname "$0")" || exit; source _includes.sh

# Send test coverage data to Coveralls (coveralls.io) if enabled.
if [[ "$ORCA_COVERALLS_ENABLE" && "$ORCA_JOB" = "ISOLATED_RECOMMENDED_COVERAGE" ]]; then
  (
    cd "$ORCA_SUT_DIR" || exit
    # shellcheck disable=SC1004
    eval 'php-coveralls -vv \
      --coverage_clover="$ORCA_COVERAGE_CLOVER_PATH" \
      --json_path="${TMPDIR:-/tmp}/coveralls.json" \
      --root_dir="$ORCA_SUT_DIR"'
  )
else
  notice "No coverage data sent to Coveralls."
fi

# Log the job on cron if telemetry is enabled.
if [[ "$TRAVIS_EVENT_TYPE" = "cron" && "$ORCA_TELEMETRY_ENABLE" = "TRUE" && "$ORCA_AMPLITUDE_API_KEY" && "$ORCA_AMPLITUDE_USER_ID" ]]; then
  orca internal:log-job
fi
if [[ "$ORCA_TELEMETRY_ENABLE" = "TRUE" ]]; then
  orca internal:log-job --simulate
else
  notice "No telemetry data sent."
fi

# Show ORCA's own current build status. A failure may signify an upstream issue
# or service level outage that could have affected this build.
# @see https://travis-ci.org/acquia/orca/branches
echo && travis history --no-interactive --repo=acquia/orca --branch=master --limit=2 --date
