# Getting Started

1. [Configuring Travis CI and GitHub Actions](#configuring-travis-ci)
1. [Local installation](#local-installation)
1. [Making ORCA aware of your package](#making-orca-aware-of-your-package)
1. [Running automated tests](#running-automated-tests)
    1. [PHPUnit](#phpunit)
    1. [Nightwatch.js](#nightwatchjs)
    1. [Tagging/grouping](#tagginggrouping)

## Configuring Travis CI and GitHub Actions

ORCA's primary use case is in a continuous integration workflow, running against pull requests and commits. It provides several scripts in `bin/travis` and `bin/actions` corresponding to build phases and steps in Travis CI and GitHub Actions, respectively:

* **`before_install.sh`** ([travis](../bin/travis/before_install.sh)|[actions](../bin/actions/before_install.sh)) configures the Travis CI environment, installs ORCA, and prepares the SUT.
* **`install.sh`** ([travis](../bin/common/install.sh)|[actions](../bin/actions/install.sh)) creates the test fixture and places the SUT.
* **`before_script.sh`** ([travis](../bin/common/before_script.sh)|[actions](../bin/actions/before_script.sh)) displays details about the fixture for debugging purposes.
* **`script.sh`** ([travis](../bin/common/script.sh)|[actions](../bin/actions/script.sh)) runs static code analysis and automated tests.
* **`before_cache.sh`** ([travis](../bin/travis/before_cache.sh)|[actions](../bin/actions/before_cache.sh)) is reserved for future use.
* **`after_success.sh`** ([travis](../bin/travis/after_success.sh)|[actions](../bin/actions/after_success.sh)) is reserved for future use.
* **`after_failure.sh`** ([travis](../bin/travis/after_failure.sh)|[actions](../bin/actions/after_failure.sh)) displays debugging information in case of failure.
* **`after_script.sh`** ([travis](../bin/travis/after_script.sh)|[actions](../bin/actions/after_script.sh)) conditionally logs the job.

See [`example/.travis.yml`](../example/.travis.yml) for an example Travis CI configuration and [`example/.github/workflows/orca.yml`](../example/.github/workflows/orca.yml) for an example GitHub Actions configuration. Features are explained in the comments.

For more complex testing needs, ORCA commands can be invoked directly. [See this this example from Lightning.](https://github.com/acquia/lightning-core/blob/8.x-3.11/tests/travis/before_script.sh)

See also [Continuous integration](understanding-orca.md#continuous-integration).

## Local installation

ORCA can also be installed and run locally for testing and development. Follow these steps to set it up:

1. Ensure you have PHP 7.2 or later (PHP 7.3 or later is recommended) with at least 256 MB of memory allocated to it and [Composer](https://getcomposer.org) installed.

1. Choose a directory to contain your package(s), e.g.:

    ```bash
    PARENT_DIR="$HOME/Projects"
    ```

1. Install ORCA and clone your package(s) each into the directory, e.g.:

    ```bash
    composer create-project acquia/orca "${PARENT_DIR}/orca"
    git clone git@github.com:acquia/EXAMPLE.git "${PARENT_DIR}/EXAMPLE"
    ```

1. Optionally make the commandline executable globally-accessible...

    - Via symlink, e.g.:

        ```bash
        ln -s /path/to/orca/bin/orca /usr/local/bin/orca
        ```

    - Or via alias, e.g.:

        ```bash
        alias orca="/path/to/orca/bin/orca"
        ```

      (Add this to your `.bash_profile`/`.bashrc` or equivalent to make it permanent.)

1. Optionally add command autocompletion to your shell:

    ```bash
    # Bash:
    bash $(/path/to/orca _completion --generate-hook)

    # Zsh:
    source <(/path/to/orca _completion --generate-hook)
    ```

Invoke ORCA from the terminal (`bin/orca`). Use the `--help` command option to learn more about the various commands or see how they're used in [`bin/travis/script.sh`](../bin/common/script.sh). Use the `fixture:run-server` command to run the web server for local development.

## Making ORCA aware of your package

If your package isn't included in [the built-in list](../config/packages.yml), ORCA won't know about it, leading to errors like the following:

```bash
$ orca init --sut=drupal/example
Error: Invalid value for "--sut" option: "drupal/example".
```

To make ORCA aware of your package you'll need to dynamically add it to the list using [environment variables](advanced-usage.md#ORCA_PACKAGES_CONFIG_ALTER). Doing this on Travis CI is covered in the `env.global` section of the [example Travis configuration](../example/.travis.yml). Locally, you must set the appropriate variable(s) in your terminal session. The assignments can be copied right from your `.travis.yml`. Just prefix them with the `export` command, e.g.:

```bash
export ORCA_PACKAGES_CONFIG_ALTER=../example/tests/packages_alter.yml
# and/or...
export ORCA_PACKAGES_CONFIG=../example/tests/packages.yml
```

Of course environment variables are ephemeral, so if you want them to persist across sessions, add them to your `.bashrc` or equivalent.

## Running automated tests

### PHPUnit

ORCA has out-of-the-box support for [PHPUnit in Drupal](https://www.drupal.org/docs/8/phpunit) using core's configuration. Existing tests that work in Drupal should work in ORCA without modification. [See a working example.](../example/tests/src/Unit/ExampleUnitTest.php)

### Nightwatch.js

ORCA has out-of-the-box support for [Nightwatch in Drupal](https://www.drupal.org/docs/8/testing/javascript-testing-using-nightwatch) using core's configuration. Existing tests that work in Drupal should work in ORCA without modification. This means, among other things, that your tests must be tagged with your package's machine name in order to be discovered. At this time, only the SUT's Nightwatch tests are run. [See a working example.](../example/tests/Drupal/Nightwatch/Tests/exampleTest.js)

### Tagging/grouping

ORCA uses groups for PHPUnit to determine which tests to run when, as depicted in the table below. Nightwatch testing only runs on the SUT and does not respect these tags at this time.

<!-- https://www.tablesgenerator.com/markdown_tables -->

|                            | (Default) | `orca_public` | `orca_ignore` |
|----------------------------|:---------:|:-------------:|:-------------:|
| Isolated tests (own)       |     ✓     |       ✓       |               |
| Integrated tests (own)     |     ✓     |       ✓       |               |
| Integrated tests (others') |           |       ✓       |               |

The default behavior is to run a test only when the package providing it is the SUT--not when it is merely included in another package's test fixture. Any test not designated public or ignored is so treated. Such tests are referred to as "private tests". This should be considered the correct choice for most tests--particularly for features that involve little or no risk of conflict with other company packages, including [isolated unit tests](glossary.md#isolated-unit-tests) by definition.

Public PHPUnit tests (`orca_public`) are _always_ run, including when testing packages other than the one providing them. Acquia's implementation, for example, a public PHPUnit test provided by Lightning API will also be run during tests of Acquia Lift, Acquia Purge, and the rest. Public tests thus lengthen builds for _all company packages_ and should be used judiciously. Reserve them for high value features with meaningful risk of being broken by other packages, and make them as fast as possible.

Ignored tests (`orca_ignore`) are "ignored" and _never_ run by ORCA. Tests should be ignored when they depend upon setup or preconditions that ORCA doesn't provide, such as a fixture with unique dependencies or a database populated by SQL dump. Once ignored, such tests can be scripted to run apart from ORCA after custom setup. In practice, it should rarely be necessary to ignore a test, as most setup and teardown can be accomplished through [PHPUnit template methods](https://phpunit.de/manual/6.5/en/fixtures.html).

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| **Getting Started**
| [CLI Commands](commands.md)
| [Advanced Usage](advanced-usage.md)
| [Project Glossary](glossary.md)
| [FAQ](faq.md)
| [Contribution Guide](CONTRIBUTING.md)
