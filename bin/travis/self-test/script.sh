#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA self-tests
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests on ORCA itself.

cd "$(dirname "$0")"; source ../_includes.sh

if [[ "$ORCA_JOB" = "STATIC_CODE_ANALYSIS" ]]; then
  (
    cd ../../../
    ./vendor/bin/security-checker security:check
    ./bin/orca qa:static-analysis ./
    ./vendor/bin/phpunit
  )
fi
