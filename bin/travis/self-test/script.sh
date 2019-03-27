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
  cd ../../../
  ./bin/orca static-analysis:run ./
  ./vendor/bin/phpunit
  cd -
fi
