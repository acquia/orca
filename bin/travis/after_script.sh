#!/usr/bin/env bash

# NAME
#     after_script.sh - Perform final, post-script tasks.
#
# SYNOPSIS
#     after_script.sh
#
# DESCRIPTION
#     Conditionally logs the job and displays upstream ORCA status.

cd "$(dirname "$0")" || exit 1; source _includes.sh

if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB after_script $ORCA_SUT_NAME"
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
