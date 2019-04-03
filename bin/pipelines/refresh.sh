#!/usr/bin/env bash

# NAME
#     refresh.sh - Refreshes the Cloud environment.
#
# SYNOPSIS
#     refresh.sh
#
# DESCRIPTION
#     Refreshes the Acquia Cloud environment by reinstalling Drupal and enabling
#     all Acquia modules.

SITE="$1"
TARGET_ENV="$2"

cd "$(dirname "$0")/../../../";

./vendor/bin/drush @$SITE.$TARGET_ENV sql:query --file=db.sql
