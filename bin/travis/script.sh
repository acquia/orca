#!/usr/bin/env bash

# Travis CI "script" step script.

set -e

if [ -z "$1" ]; then
  echo "Missing required SUT argument, e.g.:"
  echo "$0 drupal/example"
  exit 127
fi

BIN_DIR="$(cd "$(dirname "$0")/.." && pwd)"

# Run integrated tests (in the presence of other Acquia product modules).
${BIN_DIR}/orca fixture:create --sut=$1
${BIN_DIR}/orca tests:run

# Tear down the test fixture.
${BIN_DIR}/orca fixture:destroy -n

# Run isolated tests (in the absence of other Acquia product modules).
${BIN_DIR}/orca fixture:create --sut=$1 --sut-only
${BIN_DIR}/orca tests:run
