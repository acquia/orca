#!/usr/bin/env bash

# NAME
#     before_install.sh - Prepare the environment for ORCA self-tests.
#
# SYNOPSIS
#     before_install.sh
#
# DESCRIPTION
#     Places an example SUT.

cd "$(dirname "$0")" || exit 1; source ../_includes.sh

(
  cd ../../../
  cp -R example ../
  cd ../example || exit 1
  git config --local user.email "action@github.com"
  git config --local user.name "GitHub Action"
  git init
  git add --all
  git commit --message="Initial commit."
  BRANCH=$(git rev-parse --abbrev-ref HEAD)
  git branch --move "$BRANCH" feature/example
  git remote add origin git@github.com:example/example.git
)
