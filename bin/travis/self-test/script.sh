#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA self-tests.
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests on ORCA itself.

cd "$(dirname "$0")"; source _includes.sh

if [[ "$ORCA_JOB" = "STATIC_CODE_ANALYSIS" ]]; then
  (
    cd ../../../
    ./vendor/bin/phpcs
    ./vendor/bin/parallel-lint --exclude vendor .
    ./vendor/bin/phpmd . text phpmd.xml.dist --ignore-violations-on-exit
    ./vendor/bin/phpunit
    ./vendor/bin/security-checker security:check
  )
fi

if [[ "$ORCA_JOB" = "LIVE_TEST" ]]; then
  orca qa:automated-tests
fi
