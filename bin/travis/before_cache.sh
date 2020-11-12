#!/usr/bin/env bash

# NAME
#     before_cache.sh - Not yet implemented.
#
# SYNOPSIS
#     before_cache.sh
#
# DESCRIPTION
#     Reserved for future use.

if [[ "$ORCA_JOB" ]]; then
  eval "orca ci:run $ORCA_JOB before_cache $ORCA_SUT_NAME"
fi
