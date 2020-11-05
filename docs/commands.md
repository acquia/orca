# CLI Commands

* [`analyze`](#qastatic-analysis)
* [`backup`](#fixturebackup)
* [`core`](#debugcore-versions)
* [`deprecations`](#qadeprecated-code-scan)
* [`enexts`](#fixtureenable-extensions)
* [`env`](#debugenv-vars)
* [`fix`](#qafixer)
* [`guess`](#debugguess-version)
* [`help`](#help)
* [`init`](#fixtureinit)
* [`jobs`](#debugci-jobs)
* [`list`](#list)
* [`packages`](#debugpackages)
* [`phases`](#debugci-phases)
* [`phpstan`](#qadeprecated-code-scan)
* [`reset`](#fixturereset)
* [`rm`](#fixturerm)
* [`run`](#cirun)
* [`serve`](#fixturerun-server)
* [`si`](#fixtureinstall-site)
* [`st`](#fixturestatus)
* [`status`](#fixturestatus)
* [`test`](#qaautomated-tests)
* [`vars`](#debugenv-vars)

**ci:**

* [`ci:run`](#cirun)

**debug:**

* [`debug:ci-jobs`](#debugci-jobs)
* [`debug:ci-phases`](#debugci-phases)
* [`debug:core-versions`](#debugcore-versions)
* [`debug:env-vars`](#debugenv-vars)
* [`debug:guess-version`](#debugguess-version)
* [`debug:packages`](#debugpackages)

**fixture:**

* [`fixture:backup`](#fixturebackup)
* [`fixture:enable-extensions`](#fixtureenable-extensions)
* [`fixture:init`](#fixtureinit)
* [`fixture:install-site`](#fixtureinstall-site)
* [`fixture:reset`](#fixturereset)
* [`fixture:rm`](#fixturerm)
* [`fixture:run-server`](#fixturerun-server)
* [`fixture:status`](#fixturestatus)

**internal:**



**qa:**

* [`qa:automated-tests`](#qaautomated-tests)
* [`qa:deprecated-code-scan`](#qadeprecated-code-scan)
* [`qa:fixer`](#qafixer)
* [`qa:static-analysis`](#qastatic-analysis)

`help`
------

Displays help for a command

### Usage

* `help [--format FORMAT] [--raw] [--] [<command_name>]`

The help command displays help for a given command:

  php ./bin/orca help list

You can also output the help in other formats by using the --format option:

  php ./bin/orca help --format=xml list

To display the list of available commands, please use the list command.

### Arguments

#### `command_name`

The command name

* Is required: no
* Is array: no
* Default: `'help'`

### Options

#### `--format`

The output format (txt, xml, json, or md)

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `'txt'`

#### `--raw`

To output raw command help

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`list`
------

Lists commands

### Usage

* `list [--raw] [--format FORMAT] [--] [<namespace>]`

The list command lists all commands:

  php ./bin/orca list

You can also display the commands for a specific namespace:

  php ./bin/orca list test

You can also output the information in other formats by using the --format option:

  php ./bin/orca list --format=xml

It's also possible to get raw list of commands (useful for embedding command runner):

  php ./bin/orca list --raw

### Arguments

#### `namespace`

The namespace name

* Is required: no
* Is array: no
* Default: `NULL`

### Options

#### `--raw`

To output raw command list

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--format`

The output format (txt, xml, json, or md)

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `'txt'`

`ci:run`
--------

Runs an ORCA CI job phase

### Usage

* `ci:run <job> <phase> <sut>`
* `run`

Runs an ORCA CI job phase

### Arguments

#### `job`

The job name:
- STATIC_CODE_ANALYSIS: Static code analysis
- INTEGRATED_TEST_ON_OLDEST_SUPPORTED: Integrated test on oldest supported Drupal core version
- INTEGRATED_TEST_ON_LATEST_LTS: Integrated test on latest LTS Drupal core version
- INTEGRATED_TEST_ON_PREVIOUS_MINOR: Integrated test on previous minor Drupal core version
- INTEGRATED_UPGRADE_TEST_FROM_PREVIOUS_MINOR: Integrated upgrade test from previous minor Drupal core version
- ISOLATED_TEST_ON_CURRENT: Isolated test on current Drupal core version
- INTEGRATED_TEST_ON_CURRENT: Integrated test on current Drupal core version
- INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR: Integrated upgrade test to next minor Drupal core version
- INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR_DEV: Integrated upgrade test to next minor dev Drupal core version
- ISOLATED_TEST_ON_CURRENT_DEV: Isolated test on current dev Drupal core version
- INTEGRATED_TEST_ON_CURRENT_DEV: Integrated test on current dev Drupal core version
- LOOSE_DEPRECATED_CODE_SCAN: Loose deprecated code scan
- STRICT_DEPRECATED_CODE_SCAN: Strict deprecated code scan
- DEPRECATED_CODE_SCAN_W_CONTRIB: Deprecated code scan w/ contrib
- ISOLATED_TEST_ON_NEXT_MINOR: Isolated test on next minor Drupal core version
- INTEGRATED_TEST_ON_NEXT_MINOR: Integrated test on next minor Drupal core version
- ISOLATED_TEST_ON_NEXT_MINOR_DEV: Isolated test on next minor dev Drupal core version
- INTEGRATED_TEST_ON_NEXT_MINOR_DEV: Integrated test on next minor dev Drupal core version
- ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER: Isolated test on next major, latest minor beta-or-later Drupal core version
- INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER: Integrated test on next major, latest minor beta-or-later Drupal core version
- ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV: Isolated test on next major, latest minor dev Drupal core version
- INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV: Integrated test on next major, latest minor dev Drupal core version
- ISOLATED_UPGRADE_TEST_TO_NEXT_MAJOR_BETA_OR_LATER: Isolated upgrade test to next major beta-or-later Drupal core version
- ISOLATED_UPGRADE_TEST_TO_NEXT_MAJOR_DEV: Isolated upgrade test to next major dev Drupal core version

* Is required: yes
* Is array: no
* Default: `NULL`

#### `phase`

The phase name:
- before_install: Scripts to run before the install stage
- install: Scripts to run at the install stage
- before_script: Scripts to run before the script stage
- script: Scripts to run at the script stage
- before_cache: Scripts to run before storing a build cache
- after_success: Scripts to run after a successful script stage
- after_failure: Scripts to run after a failing script stage
- before_deploy: Scripts to run before the deploy stage
- deploy: Scripts to run at the deploy stage
- after_deploy: Scripts to run after the deploy stage
- after_script: Scripts to run as the last stage

* Is required: yes
* Is array: no
* Default: `NULL`

#### `sut`

The system under test (SUT) in the form of its package name, e.g., "drupal/example"

* Is required: yes
* Is array: no
* Default: `NULL`

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`debug:ci-jobs`
---------------

Displays ORCA CI jobs

### Usage

* `debug:ci-jobs`
* `jobs`

Displays ORCA CI jobs

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`debug:ci-phases`
-----------------

Displays ORCA CI phases

### Usage

* `debug:ci-phases`
* `phases`

Displays ORCA CI phases

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`debug:core-versions`
---------------------

Provides an overview of Drupal Core versions

### Usage

* `debug:core-versions [--examples] [--resolve]`
* `core`

Provides an overview of Drupal Core versions

### Options

#### `--examples`

Include example version strings

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--resolve`

Include the exact versions Composer would actually install. Makes HTTP requests and increases execution time

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`debug:env-vars`
----------------

Displays ORCA environment variables

### Usage

* `debug:env-vars`
* `env`
* `vars`

Displays ORCA environment variables

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`debug:guess-version`
---------------------

Gets the version Composer guesses for a given path repository

### Usage

* `debug:guess-version <path>`
* `guess`

Gets the version Composer guesses for a given path repository

### Arguments

#### `path`

The path to guess the version for

* Is required: yes
* Is array: no
* Default: `NULL`

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`debug:packages`
----------------

Displays the active packages configuration

### Usage

* `debug:packages [<core>]`
* `packages`

Displays the active packages configuration

### Arguments

#### `core`

A Drupal core version to target:
- OLDEST_SUPPORTED: Oldest supported Drupal core version, e.g., "8.8.1"
- LATEST_LTS: Latest LTS Drupal core version, e.g., "8.9.1"
- PREVIOUS_MINOR: Previous minor Drupal core version, e.g., "9.1.1"
- CURRENT: Current Drupal core version, e.g., "9.2.1"
- CURRENT_DEV: Current dev Drupal core version, e.g., "9.2.x-dev"
- NEXT_MINOR: Next minor Drupal core version, e.g., "9.3.0-alpha1"
- NEXT_MINOR_DEV: Next minor dev Drupal core version, e.g., "9.3.x-dev"
- NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER: Next major, latest minor beta-or-later Drupal core version, e.g., "10.0.0-beta1"
- NEXT_MAJOR_LATEST_MINOR_DEV: Next major, latest minor dev Drupal core version, e.g., "10.0.x-dev"
- Any version string Composer understands, see https://getcomposer.org/doc/articles/versions.md

* Is required: no
* Is array: no
* Default: `NULL`

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`fixture:backup`
----------------

Backs up the test fixture

### Usage

* `fixture:backup [-f|--force]`
* `backup`

Backs up the current state of the fixture, including codebase and Drupal database.

### Options

#### `--force|-f`

Backup without confirmation

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`fixture:enable-extensions`
---------------------------

Enables all company Drupal extensions

### Usage

* `fixture:enable-extensions`
* `enexts`

Enables all company Drupal extensions

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`fixture:init`
--------------

Creates the test fixture

### Usage

* `fixture:init [-f|--force] [--sut SUT] [--sut-only] [--bare] [--core CORE] [--dev] [--profile PROFILE] [--project-template PROJECT-TEMPLATE] [--ignore-patch-failure] [--no-sqlite] [--no-site-install] [--prefer-source] [--symlink-all]`
* `init`

Creates a Drupal site build, includes the system under test using Composer, optionally includes all other company packages, and installs Drupal.

### Options

#### `--force|-f`

If the fixture already exists, remove it first without confirmation

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--sut`

The system under test (SUT) in the form of its package name, e.g., "drupal/example"

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `NULL`

#### `--sut-only`

Add only the system under test (SUT). Omit all other non-required company packages

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--bare`

Omit all non-required company packages

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--core`

Change the version of Drupal core installed:
- OLDEST_SUPPORTED: Oldest supported Drupal core version, e.g., "8.8.1"
- LATEST_LTS: Latest LTS Drupal core version, e.g., "8.9.1"
- PREVIOUS_MINOR: Previous minor Drupal core version, e.g., "9.1.1"
- CURRENT: Current Drupal core version, e.g., "9.2.1"
- CURRENT_DEV: Current dev Drupal core version, e.g., "9.2.x-dev"
- NEXT_MINOR: Next minor Drupal core version, e.g., "9.3.0-alpha1"
- NEXT_MINOR_DEV: Next minor dev Drupal core version, e.g., "9.3.x-dev"
- NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER: Next major, latest minor beta-or-later Drupal core version, e.g., "10.0.0-beta1"
- NEXT_MAJOR_LATEST_MINOR_DEV: Next major, latest minor dev Drupal core version, e.g., "10.0.x-dev"
- Any version string Composer understands, see https://getcomposer.org/doc/articles/versions.md

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `'CURRENT'`

#### `--dev`

Use dev versions of company packages

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--profile`

The Drupal installation profile to use, e.g., "minimal". ("orca" is a pseudo-profile based on "minimal", with the Toolbar module enabled and Seven as the admin theme)

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `'orca'`

#### `--project-template`

The Composer project template used to create the fixture

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `NULL`

#### `--ignore-patch-failure`

Do not exit on failure to apply Composer patches. (Useful for debugging failures)

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-sqlite`

Use the default database settings instead of SQLite

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-site-install`

Do not install Drupal. Supersedes the "--profile" option

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--prefer-source`

Force installation of non-company packages from sources when possible, including VCS information. (Company packages are always installed from source.) Useful for core and contrib work

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--symlink-all`

Symlink all possible company packages via local path repository. Packages absent from the expected location will be installed normally

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`fixture:install-site`
----------------------

Installs the site

### Usage

* `fixture:install-site [-f|--force] [--profile PROFILE]`
* `si`

Installs Drupal and enables company extensions.

### Options

#### `--force|-f`

Install without confirmation

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--profile`

The Drupal installation profile to use, e.g., "minimal". ("orca" is a pseudo-profile based on "testing", with the Toolbar module enabled and Seven as the admin theme)

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `'orca'`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`fixture:reset`
---------------

Resets the test fixture

### Usage

* `fixture:reset [-f|--force]`
* `reset`

Restores the original state of the fixture, including codebase and Drupal database.

### Options

#### `--force|-f`

Remove without confirmation

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`fixture:rm`
------------

Removes the test fixture

### Usage

* `fixture:rm [-f|--force]`
* `rm`

Removes the entire site build directory and Drupal database.

### Options

#### `--force|-f`

Remove without confirmation

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`fixture:run-server`
--------------------

Runs the web server for development

### Usage

* `fixture:run-server`
* `serve`

Runs the web server for development

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`fixture:status`
----------------

Provides an overview of the fixture

### Usage

* `fixture:status`
* `status`
* `st`

Provides an overview of the fixture

### Options

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`qa:automated-tests`
--------------------

Runs automated tests

### Usage

* `qa:automated-tests [--sut SUT] [--sut-only] [--phpunit] [--no-servers]`
* `test`

Runs automated tests

### Options

#### `--sut`

The system under test (SUT) in the form of its package name, e.g., "drupal/example"

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `NULL`

#### `--sut-only`

Run tests from only the system under test (SUT). Omit tests from all other company packages

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--phpunit`

Run only PHPUnit tests

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-servers`

Don't run the ChromeDriver and web servers

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`qa:deprecated-code-scan`
-------------------------

Scans for deprecated code

### Usage

* `qa:deprecated-code-scan [--sut SUT] [--contrib]`
* `deprecations`
* `phpstan`

Scans for deprecated code

### Options

#### `--sut`

Scan the system under test (SUT). Provide its package name, e.g., "drupal/example"

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `NULL`

#### `--contrib`

Scan contributed projects

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`qa:fixer`
----------

Fixes issues found by static analysis tools

### Usage

* `qa:fixer [--composer] [--phpcbf] [--phpcs-standard PHPCS-STANDARD] [--] <path>`
* `fix`

Tools can be specified individually or in combination. If none are specified, all will be run.

### Arguments

#### `path`

The path to fix issues in

* Is required: yes
* Is array: no
* Default: `NULL`

### Options

#### `--composer`

Run the Composer Normalizer tool

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--phpcbf`

Run the PHP Code Beautifier and Fixer tool

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--phpcs-standard`

Change the PHPCS standard used:
- AcquiaPHP: Contains sniffs applicable to all PHP projects
- AcquiaDrupalStrict: Recommended for new Drupal projects and teams familiar with Drupal coding standards
- AcquiaDrupalTransitional: A relaxed standard for legacy Drupal codebases or teams new to Drupal coding standards

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `'AcquiaDrupalTransitional'`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

`qa:static-analysis`
--------------------

Runs static analysis tools

### Usage

* `qa:static-analysis [--composer] [--coverage] [--phpcs] [--phpcs-standard PHPCS-STANDARD] [--phplint] [--phploc] [--phpmd] [--yamllint] [--] <path>`
* `analyze`

Tools can be specified individually or in combination. If none are specified, all will be run.

### Arguments

#### `path`

The path to analyze

* Is required: yes
* Is array: no
* Default: `NULL`

### Options

#### `--composer`

Run the Composer validation tool

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--coverage`

Run the code coverage estimator. Implies "--phploc"

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--phpcs`

Run the PHP Code Sniffer tool

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--phpcs-standard`

Change the PHPCS standard used:
- AcquiaPHP: Contains sniffs applicable to all PHP projects
- AcquiaDrupalStrict: Recommended for new Drupal projects and teams familiar with Drupal coding standards
- AcquiaDrupalTransitional: A relaxed standard for legacy Drupal codebases or teams new to Drupal coding standards

* Accept value: yes
* Is value required: yes
* Is multiple: no
* Default: `'AcquiaDrupalTransitional'`

#### `--phplint`

Run the PHP Lint tool

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--phploc`

Run the PHPLOC tool

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--phpmd`

Run the PHP Mess Detector tool

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--yamllint`

Run the YAML Lint tool

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--help|-h`

Display this help message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--quiet|-q`

Do not output any message

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--verbose|-v|-vv|-vvv`

Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--version|-V`

Display this application version

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--ansi`

Force ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-ansi`

Disable ANSI output

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

#### `--no-interaction|-n`

Do not ask any interactive question

* Accept value: no
* Is value required: no
* Is multiple: no
* Default: `false`

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| [Getting Started](getting-started.md)
| **CLI Commands**
| [Advanced Usage](advanced-usage.md)
| [Project Glossary](glossary.md)
| [FAQ](faq.md)
| [Contribution Guide](CONTRIBUTING.md)
