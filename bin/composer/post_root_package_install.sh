#!/usr/bin/env bash

# NAME
#     post_root_package_install.sh - Prepare ORCA for installation.
#
# SYNOPSIS
#     post_root_package_install.sh
#
# DESCRIPTION
#     Prepares ORCA itself for installation via `composer create-project`. Runs
#     after the root package has been installed, during the create-project
#     command. See https://getcomposer.org/doc/articles/scripts.md#command-events.

if [[ "$TRAVIS" ]]; then
  # Reverse "surprise" release of Composer 2.
  composer self-update --rollback
fi
