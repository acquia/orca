#!/usr/bin/env bash

# NAME
#     before_script.sh - Display details about the fixture.
#
# SYNOPSIS
#     before_script.sh
#
# DESCRIPTION
#     Displays details about the fixture for debugging purposes.

cd "$(dirname "$0")" || exit; source _includes.sh

# Exit early in the absence of a fixture.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

# Display installed Composer package information.
composer -d"$ORCA_FIXTURE_DIR" show --latest --ansi

# Display the list of available Drupal extensions (modules and themes).
drush --no-ansi pm:list --fields=package,display_name,type,status,version || true

# Display basic Drupal site details.
drush core-status

eval "orca ci:run $ORCA_JOB before_script"
