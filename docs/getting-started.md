# Getting Started

1. [Configuring Travis CI](#configuring-travis-ci)
1. [Local installation](#local-installation)
1. [Running automated tests](#running-automated-tests)
    1. [PHPUnit](#phpunit)
    1. [Behat](#behat)
    1. [Tagging/grouping](#tagginggrouping)

## Configuring Travis CI

ORCA's primary use case is in a continuous integration workflow, running against pull requests and commits. It provides several scripts in `bin/travis` corresponding to Travis CI phases:

* **[`before_install.sh`](../bin/travis/before_install.sh)** prepares the environment and installs ORCA.
* **[`install.sh`](../bin/travis/install.sh)** creates the test fixture and places the system under test (SUT).
* **[`before_script.sh`](../bin/travis/before_script.sh)** displays details about the fixture for debugging purposes.
* **[`script.sh`](../bin/travis/script.sh)** runs static analysis and automated tests.
* **[`before_cache.sh`](../bin/travis/before_cache.sh)** is reserved for future use.
* **[`after_success.sh`](../bin/travis/after_success.sh)** is reserved for future use.
* **[`after_failure.sh`](../bin/travis/after_failure.sh)** displays debugging information in case of job failure.
* **[`after_script.sh`](../bin/travis/after_script.sh)** is reserved for future use.

See [`example/.travis.yml`](../example/.travis.yml) for an example Travis CI configuration. Features are explained in the comments.

For more complex testing needs, ORCA commands can be invoked directly. [See this this example from Lightning.](https://github.com/acquia/lightning-core/blob/8.x-3.11/tests/travis/before_script.sh)

See also [Continuous integration](understanding-orca.md#continuous-integration).

## Local installation

ORCA can also be installed and run locally for testing and development. Follow these steps to set it up:

1. Ensure you have PHP 7.2 or later with at least 256 MB of memory allocated to it and [Composer](https://getcomposer.org) installed.

1. Choose a directory to contain your package(s), e.g.:

    ```bash
    PARENT_DIR="$HOME/Projects"
    ```

1. Clone ORCA and your package(s) each into the directory, e.g.:

    ```bash
    git clone git@github.com:acquia/orca.git "${PARENT_DIR}/orca"
    git clone git@github.com:acquia/EXAMPLE.git "${PARENT_DIR}/EXAMPLE"
    ```

1. Install ORCA with Composer, e.g.:

    ```bash
    composer install --no-dev --working-dir="${PARENT_DIR}/orca"
    ```

1. Optionally make the commandline executable globally-accessible...

    - Via symlink, e.g.:

        ```bash
        ln -s path/to/orca/bin/orca /usr/local/bin/orca
        ```

    - Or via alias, e.g.:

        ```bash
        alias orca="path/to/orca/bin/orca"
        ```

      (Add this to your `.bash_profile`/`.bashrc` or equivalent to make it permanent.)

1. Optionally add command autocompletion to your shell:

    ```bash
    # Bash:
    bash $(path/to/orca _completion --generate-hook)

    # Zsh:
    source <(path/to/orca _completion --generate-hook)
    ```

Invoke ORCA from the terminal (`bin/orca`). Use the `--help` command option to learn more about the various commands or see how they're used in [`bin/travis/script`](../bin/travis/script). Use the `fixture:run-server` command to run the web server for local development.

## Running automated tests

### PHPUnit

ORCA has out-of-the-box support for [PHPUnit in Drupal 8](https://www.drupal.org/docs/8/phpunit) using core's configuration. Existing tests that work in Drupal should work in ORCA with no modification. [See a working example.](../example/tests/src/Unit/ExampleUnitTest.php)

#### Behat

Because Drupal core has no built-in support for Behat, special configuration is required for ORCA to run it:

* Add a `behat.yml` in the root of your package repo to [configure your test profile](http://behat.org/en/latest/user_guide/configuration.html). (See [`example/behat.yml`](../example/behat.yml).)
* Add your [feature context(s)](http://behat.org/en/latest/user_guide/context.html) to a designated directory, e.g., `tests/features/bootstrap`. (See [`example/tests/features/bootstrap/FeatureContext.php`](../example/tests/features/bootstrap/FeatureContext.php).)
* Add your [feature files](http://behat.org/en/latest/user_guide/features_scenarios.html) to a designated directory, e.g., `tests/features`. (See [`example/tests/features`](../example/tests/features).)
* Add the new classes to your Composer autoloader classmap so the deprecated code scanner can find them. (See [`example/composer.json`](../example/composer.json).)

For more information on using Behat with Drupal, see [the Behat website](http://behat.org/) and [the Drupal Extension to Behat and Mink](https://behat-drupal-extension.readthedocs.io/). You might also find the [Drupal Spec Tool](https://github.com/acquia/drupal-spec-tool) interesting.

### Tagging/grouping

ORCA uses tags (for Behat) and groups (for PHPUnit) to determine which tests to run when, as depicted in the table below:

<!-- https://www.tablesgenerator.com/markdown_tables -->

|                            | (Default) | `orca_public` | `orca_ignore` |
|----------------------------|:---------:|:-------------:|:-------------:|
| Isolated tests (own)       |     ✓     |       ✓       |               |
| Integrated tests (own)     |     ✓     |       ✓       |               |
| Integrated tests (others') |           |       ✓       |               |

The default behavior is to run a test only when the package providing it is the SUT--not when it is merely included in another package's test fixture. Any test not designated public or ignored is so treated. Such tests are referred to as "private tests". This should be considered the correct choice for most tests--particularly for features that involve little or no risk of conflict with other Acquia packages, including [isolated unit tests](http://wiki.c2.com/?UnitTestIsolation) by definition.

Public PHPUnit tests (`orca_public`) are _always_ run, including when testing packages other than the one providing them. (Behat has proved a source of too much instability to inflict across the board, so the default Travis CI jobs _never_ run non-SUT Behat tests.) For example, a public PHPUnit test provided by Lightning API will also be run during tests of Acquia Commerce Manager, Acquia Lift, and the rest. Public tests thus lengthen builds for _all Acquia packages_ and should be used judiciously. Reserve them for high value features with meaningful risk of being broken by other packages, and make them as fast as possible.

Ignored tests (`orca_ignore`) are "ignored" and _never_ run by ORCA. Tests should be ignored when they depend upon setup or preconditions that ORCA doesn't provide, such as a fixture with unique dependencies or a database populated by SQL dump. Once ignored, such tests can be scripted to run apart from ORCA after custom setup. In practice, it should rarely be necessary to ignore a test, as most setup and teardown can be accomplished through [Behat hooks](http://behat.org/en/latest/user_guide/context/hooks.html) and [PHPUnit template methods](https://phpunit.de/manual/6.5/en/fixtures.html).

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| **Getting Started**
| [Advanced Usage](advanced-usage.md)
| [Project Glossary](glossary.md)
| [FAQ](faq.md)
| [Contribution Guide](CONTRIBUTING.md)
