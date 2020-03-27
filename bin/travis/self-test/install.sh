#!/usr/bin/env bash

# NAME
#     install.sh - Create the test fixture.
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture and places the SUT.

cd "$(dirname "$0")" || exit; source _includes.sh

if [[ "$ORCA_JOB" = "LIVE_TEST" ]]; then
  orca debug:packages CURRENT_RECOMMENDED
  orca fixture:init -f --core=CURRENT_RECOMMENDED
fi
