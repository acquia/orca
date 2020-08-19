#!/usr/bin/env bash

# NAME
#     before_install.sh - Prepare the environment for ORCA self-tests.
#
# SYNOPSIS
#     before_install.sh
#
# DESCRIPTION
#     Places an example SUT.

cd "$(dirname "$0")" || exit; source ../_includes.sh

(
  cd ../../../
  cp -R example ../
  cd ../example || exit
  git init
  git add --all
  git commit --message="Initial commit."
  git branch --move master feature/example
  git remote add origin git@github.com:example/example.git
)
