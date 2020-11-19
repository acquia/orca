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

GITHUB_CHANGELOG_GENERATOR=$(find /usr/local/lib/ruby/gems/*/bin/github_changelog_generator | tail -1)
FUTURE_RELEASE=$(cat config/VERSION)

eval "$GITHUB_CHANGELOG_GENERATOR" \
  --user=acquia \
  --project=orca \
  --output=docs/CHANGELOG.md \
  --no-issues-wo-labels \
  --no-issues-wo-labels \
  --exclude-labels=dependencies,duplicate,question,invalid,wontfix \
  --since-tag=v2.11.4 \
  --future-release="$FUTURE_RELEASE" \
  --release-branch=develop
