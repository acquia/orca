#!/usr/bin/env bash

# NAME
#     _includes.sh - Include reusable code.
#
# SYNOPSIS
#     cd "$(dirname "$0")" || exit; source _includes.sh
#
# DESCRIPTION
#     Includes common features used by the Travis CI scripts.

source ../_includes.sh

if [[ "$ORCA_LIVE_TEST" ]]; then
  unset ORCA_PACKAGES_CONFIG
  unset ORCA_PACKAGES_CONFIG_ALTER
fi
