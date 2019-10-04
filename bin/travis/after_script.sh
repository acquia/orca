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

# Log the job on cron if telemetry is enabled.
if [[ "$TRAVIS_EVENT_TYPE" = "cron" && "$ORCA_TELEMETRY_ENABLE" && "$ORCA_AMPLITUDE_API_KEY" && "$ORCA_AMPLITUDE_USER_ID" ]]; then
  orca internal:log-job
fi

# Show ORCA's own current build status. A failure may signify an upstream issue
# or service level outage that could have affected this build.
# @see https://travis-ci.org/acquia/orca/branches
travis history --no-interactive --repo=acquia/orca --branch=master --limit=1 --date
