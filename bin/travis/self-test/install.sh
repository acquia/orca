#!/usr/bin/env bash

# NAME
#     install.sh - Install Travis CI dependencies for ORCA self-tests
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Places an example SUT.

cd "$(dirname "$0")"; source ../_includes.sh

cd ../../../
cp -R example ../
git -C ../example init
git -C ../example add --all
git -C ../example commit --message="Initial commit."
git -C ../example branch --move master feature/example
cd -
