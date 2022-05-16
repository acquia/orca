#!/usr/bin/env bash

# NAME
#     script.sh - Run ORCA self-tests.
#
# SYNOPSIS
#     script.sh
#
# DESCRIPTION
#     Runs static code analysis and automated tests on ORCA itself.

cd "$(dirname "$0")" || exit 1; source _includes.sh

cd ../../../ || exit 1

XDEBUG_IS_ENABLED=$(php -r 'echo function_exists("xdebug_get_code_coverage") ? "TRUE" : "FALSE";')

if [[ "$ORCA_COVERAGE_ENABLE" == TRUE && "$XDEBUG_IS_ENABLED" == "FALSE" ]]; then
  echo "ORCA_COVERAGE_ENABLE is on but Xdebug is disabled"
  exit 1
fi

if [[ "$ORCA_COVERAGE_ENABLE" == FALSE && "$XDEBUG_IS_ENABLED" == "TRUE" ]]; then
  echo "ORCA_COVERAGE_ENABLE is off but Xdebug is enabled"
  exit 1
fi

if [[ "$ORCA_JOB" == "STATIC_CODE_ANALYSIS" ]]; then
  ./vendor/bin/phpcs
  ./vendor/bin/parallel-lint --exclude vendor .
  ./vendor/bin/phpstan analyse src
  ./vendor/bin/phan -k phan.php --allow-polyfill-parser
  ./vendor/bin/phpmd . text phpmd.xml.dist --ignore-violations-on-exit

  echo

  if [[ "$ORCA_COVERAGE_ENABLE" == TRUE ]]; then
    eval './vendor/bin/phpunit --coverage-clover="$ORCA_SELF_TEST_COVERAGE_CLOVER"'
  else
    eval './vendor/bin/phpunit'
  fi
fi

if [[ "$ORCA_LIVE_TEST" ]]; then
  orca qa:automated-tests
fi
