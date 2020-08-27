#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA self-tests.
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests on ORCA itself.

cd "$(dirname "$0")" || exit; source _includes.sh

cd ../../../

if [[ "$ORCA_JOB" = "STATIC_CODE_ANALYSIS" ]]; then
  ./vendor/bin/phpcs
  ./vendor/bin/parallel-lint --exclude vendor .
  ./vendor/bin/phpmd . text phpmd.xml.dist --ignore-violations-on-exit
  ./vendor/bin/security-checker security:check
fi

echo
if [[ "$ORCA_JOB" == "ISOLATED_RECOMMENDED_COVERAGE" ]]; then
  eval './vendor/bin/phpunit --coverage-clover="$ORCA_SELF_TEST_COVERAGE"'
else
  eval './vendor/bin/phpunit'
fi

if [[ "$ORCA_JOB" = "LIVE_TEST" ]]; then
  orca qa:automated-tests
fi
