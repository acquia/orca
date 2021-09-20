#!/usr/bin/env bash

# NAME
#     example.sh - Provide an example custom GitHub Actions script.
#
# SYNOPSIS
#     example.sh
#
# DESCRIPTION
#     Provides an example of customizing a GitHub Actions ORCA build by adding or
#     modifying jobs. See live working examples:
#     - https://github.com/acquia/coding-standards-php/tree/v0.5.0/bin/actions
#     - https://github.com/acquia/drupal-spec-tool/tree/4.0.1/bin/actions
#     - https://github.com/acquia/orca/tree/v2.11.3/bin/actions/self-test
#
#     Remember to make your script executable! E.g.:
#     chmod u+x bin/actions/example.sh

# Make bash resolve paths from the location of this script, not the CWD (current
# working directory) of the user or process that called it.
cd "$(dirname "$0")" || exit 1

# Reuse ORCA's own includes for its $PATH additions and environment variables.
source ../../../orca/bin/actions/_includes.sh

# GitHub Actions provides numerous general purpose environment variables you can use.
# @see https://docs.github.com/en/actions/reference/environment-variables#default-environment-variables
echo "The SUT is cloned at $GITHUB_WORKSPACE"

# ORCA provides additional special purpose environment variables. Use the below
# command to see the list with current values. Its output is included in the
# before_install phase of all GitHub Actions jobs for debugging purposes.
orca debug:env-vars

# Unconditioned statements will be executed on every job.
echo "The current job is $ORCA_JOB."

# Target an out-of-the-box ORCA job to modify its behavior.
if [[ "$ORCA_JOB" == "ISOLATED_TEST_ON_CURRENT" ]]; then
  # For example, add test dependencies before running automated tests.
  if [[ -d "$ORCA_FIXTURE_DIR" ]]; then
    (
      cd "$ORCA_FIXTURE_DIR" || exit
      composer require drupal/example
    )
  fi
fi

# Target any condition of your own making, e.g., an environment variable set in
# your orca.yml.
if [[ "$MY_CONDITION" == "TRUE" ]]; then
  echo "\$MY_CONDITION attained"
fi
