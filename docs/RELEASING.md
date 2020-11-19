# Releasing a New Version

For project maintainers.

1. Before committing to a release...
    1. [Check the issue queue](https://github.com/acquia/orca/issues) for critical issues.
    1. Search the codebase for important `@todo` comments.
1. Run automated tests with `composer coverage`.
1. Create a release tag with [Gitflow](https://github.com/nvie/gitflow):
    1. Choose a [semantic version](https://semver.org/) number (`x.y.z`).
    1. Start the release with `git flow release start x.y.z`.
    1. Set the version to `x.y.z` in in `config/VERSION` and commit.
    1. Update the changelog with `composer changelog` and commit.
    1. Update `commands.md` with `composer update-docs` and commit.
    1. Finish the release with `git flow release finish x.y.z`.
    1. Return the version to dev in `config/VERSION`, i.e., `x.y.z-dev` and commit.
    1. Push the release tag to GitHub along with the updated `develop` and `master` branches.
1. [Create a GitHub release.](https://help.github.com/articles/creating-releases/)
    1. Set the tag version and release title both to the new version number.
    1. Use the changelog for the description.
