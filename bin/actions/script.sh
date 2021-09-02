#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA tests.
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests.

cd "$(dirname "$0")" || exit; source _includes.sh

if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB script $ORCA_SUT_NAME -vvv"
fi

