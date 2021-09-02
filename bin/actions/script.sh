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
  eval "orca qa:automated-tests --sut=acquia/blt --sut-only --no-servers -vvv"
fi

