#!/usr/bin/env bash

# NAME
#     before_script.sh - Display details about the fixture.
#
# SYNOPSIS
#     before_script.sh
#
# DESCRIPTION
#     Displays information about installed Composer packages and Drupal
#     projects.

cd "$(dirname "$0")"; source _includes.sh

# Exit early in the absence of a fixture.
[[ -d "$ORCA_FIXTURE_DIR" ]] || exit 0

# Display installed Composer packages.
composer -d${ORCA_FIXTURE_DIR} show

# Display outdated Composer packages information.
composer -d${ORCA_FIXTURE_DIR} outdated

# Display the list of available Drupal extensions (modules and themes).
drush --no-ansi pm:list --fields=package,display_name,type,status,version || true

# Display basic Drupal site details.
drush core-status
