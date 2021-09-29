#!/usr/bin/env bash

# NAME
#     changelog.sh - Generate a changelog.
#
# SYNOPSIS
#     changelog.sh
#
# DESCRIPTION
#     Generates a changelog using the GitHub Changelog Generator:
#     https://github.com/github-changelog-generator/github-changelog-generator
#     Only tested on macOS.

cd "$(dirname "$0")/.." || exit

GITHUB_CHANGELOG_GENERATOR=$(which github_changelog_generator | tail -1)
FUTURE_RELEASE=$(cat config/VERSION)

set -v

eval "$GITHUB_CHANGELOG_GENERATOR" \
  --base=docs/HISTORY.md \
  --user=acquia \
  --project=orca \
  --output=docs/CHANGELOG.md \
  --no-issues-wo-labels \
  --exclude-labels=duplicate,question,invalid,wontfix \
  --exclude-tags-regex='v[1-2].*' \
  --since-tag='v3.9.0' \
  --future-release="$FUTURE_RELEASE" \
  --release-branch=develop
