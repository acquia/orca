#!/usr/bin/env bash

# NAME
#     _includes.sh - Include reusable code.
#
# SYNOPSIS
#     cd "$(dirname "$0")" || exit; source _includes.sh
#
# DESCRIPTION
#     Includes common features used by the GitHub Actions scripts.

source ../_includes.sh

if [[ "$ORCA_LIVE_TEST" ]]; then
  set +e
  notice "This job is allowed to fail and will report as passing regardless of outcome."
  unset ORCA_PACKAGES_CONFIG
  unset ORCA_PACKAGES_CONFIG_ALTER
fi
