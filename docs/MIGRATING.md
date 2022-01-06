# Migrating from Travis CI to GitHub Actions

ORCA is removing support for [Travis CI](https://travis-ci.org/). [GitHub Actions](https://github.com/features/actions) is the preferred alternative to Travis CI for open-source projects. This document describes how to migrate a project from Travis CI to GitHub Actions.

> n.b. "GitHub Actions" refers both to the CI service as a whole and individual components (lower-case "actions") that can be incorporated into your CI job, as described below.

## Comparison of Travis CI and GitHub Actions features

- GitHub Actions allows you to re-use bundled features ("actions") provided by GitHub and the community. Actions are essentially mini-applications that help accomplish common CI use cases such as checking out code, installing PHP, or creating a GitHub release based on the outcome of a job.
- Both services execute jobs based on definitions provided via a YAML file. Travis CI reads `.travis.yml` from a repository root directory, whereas GitHub Actions reads YAML files in the `.github/workflows` directory.
- Travis CI requires a paid subscription, though it does provide an open-source tier with certain restrictions. GitHub Actions is free and fully functional for all open-source projects.
- Both services can build on Windows, macOS and Linux.
- Both services support running jobs on pull requests, commits, and cron.
- GitHub Actions provides a more robust security model, including API tokens that are generated on a per-job basis, tokens that are read-only for pull requests from forks, and the option to require manual approval prior to job execution.
- Travis CI executes jobs in a rigidly-defined series of "stages", whereas GitHub Actions allows defining an arbitrary number of "steps".
- Both services support build matrices, which dynamically create jobs based on combinations of job variables. However, whereas Travis CI created a build matrix directly from first-class primitives such as PHP versions, GitHub Actions requires defining an intermediary set of [matrix variables](https://docs.github.com/en/actions/learn-github-actions/workflow-syntax-for-github-actions#jobsjob_idstrategymatrix). While this is generally more flexible than Travis CI build matrices, it also requires a little more YAML boilerplate.

## Migrating an ORCA project from Travis CI to GitHub Actions

ORCA provides self-documented template YAML files for Travis CI and GitHub Actions via its [example project](https://github.com/acquia/orca/tree/develop/example). The recommended process to migrate from Travis CI to GitHub Actions is to:

1. Compare your project's `.travis.yml` to the [stock template](https://github.com/acquia/orca/blob/develop/example/.travis.yml) and document any differences (i.e., customizations). Especially, note environment variables such as `ORCA_SUT_NAME` and `ORCA_SUT_BRANCH`.
1. Remove `.travis.yml` from your project and copy ORCA's [GitHub Actions template workflow](https://github.com/acquia/orca/tree/develop/example/.github/workflows) to the root directory.
1. Apply to your GitHub Actions workflow any customizations documented in step 1.

Be aware of the following differences in how ORCA behaves between Travis CI and GitHub Actions:

- Only the latest release of ORCA 3 supports GitHub Actions (i.e. you must use `ORCA_VERSION: ^3` in your job definition)
- ORCA uses the same [shell scripts](https://github.com/acquia/orca/tree/develop/bin/ci) (organized by Travis CI job stage) between Travis CI and GitHub Actions. However, these scripts have been modified to accommodate GitHub Actions. If you've copied and customized these scripts in your project, you'll need to update your scripts to reflect these changes.
- Travis CI had a concept of "allowed failures", i.e. jobs that were allowed to fail without failing the entire build. GitHub Actions does not support allowed failures. Thus, ORCA now natively handles allowed failures in GitHub Actions. Jobs that are allowed to fail ([as defined by ORCA itself](https://github.com/acquia/orca/blob/bf93dbfb13897ac523d4e9cb1df8dee9f7e7aade/bin/ci/_includes.sh#L103)) will always report as passing even in the case of failures.
- Caching is no longer provided out of the box in GitHub Actions, as it provides no appreciable performance improvement for most projects. Refer to the [GitHub Actions cache documentation](https://github.com/actions/cache) if you need this feature.

## Getting help

If you have any trouble with the migration, consult the following resources:

- [GitHub Actions documentation](https://docs.github.com/en/actions)
- [GitHub Actions: Migrating from Travis CI](https://docs.github.com/en/actions/migrating-to-github-actions/migrating-from-travis-ci-to-github-actions)
- Start a discussion on ORCA's GitHub repository, or open an issue if you've found a bug or have a specific feature request.
