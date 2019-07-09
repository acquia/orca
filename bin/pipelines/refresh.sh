#!/usr/bin/env bash

# NAME
#     refresh.sh - Refreshes the Cloud environment.
#
# SYNOPSIS
#     refresh.sh <AH_SITE_GROUP> <AH_SITE_ENVIRONMENT>
#
# DESCRIPTION
#     Refreshes the Acquia Cloud environment by reinstalling Drupal and enabling
#     all Acquia modules. This script is copied to the Cloud Hooks directory on
#     Acquia Pipelines builds (see acquia-pipelines.yml) and executed via cron:
#
#     /var/www/html/${AH_SITE_NAME}/hooks/common/post-code-deploy/refresh.sh \
#         ${AH_SITE_GROUP} ${AH_SITE_ENVIRONMENT} &>> \
#         /var/log/sites/${AH_SITE_NAME}/logs/$(hostname -s)/drush-cron.log
#
# OPTIONS
#     <AH_SITE_GROUP>
#         The site group as stored in the AH_SITE_GROUP environment variable,
#         e.g., "orca".
#     <AH_SITE_ENVIRONMENT>
#         The site environment as stored in the AH_SITE_ENVIRONMENT environment
#         variable, e.g., "dev" or "prod".

SITE="$1"
TARGET_ENV="$2"

cd "$(dirname "$0")/../../../";

./vendor/bin/drush @$SITE.$TARGET_ENV sql:query --file=db.sql
./vendor/bin/drush @$SITE.$TARGET_ENV cache:rebuild
