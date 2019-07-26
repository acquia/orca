#!/usr/bin/env bash

# NAME
#     after_script.sh - Perform final, post-script tasks.
#
# SYNOPSIS
#     after_script.sh
#
# DESCRIPTION
#     Logs the job if telemetry is enabled.

cd "$(dirname "$0")" || exit; source _includes.sh

if [[ "$TRAVIS_EVENT_TYPE" = "cron" && "$ORCA_TELEMETRY_ENABLE" && "$ORCA_AMPLITUDE_API_KEY" && "$ORCA_AMPLITUDE_USER_ID" ]]; then
  orca internal:log-event TRAVIS_CI_JOB
fi
