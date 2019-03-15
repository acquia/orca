# Getting Started

1. [Configuring Travis CI](#configuring-travis-ci)
1. [Local installation](#local-installation)
1. [Designing automated tests](#designing-automated-tests)
    1. [Tagging/grouping](#tagginggrouping)

## Configuring Travis CI

ORCA's primary use case is in a continuous integration workflow, running against pull requests and commits. It provides two scripts corresponding to Travis CI hooks:

* **[`bin/travis/install`](../bin/travis/install)** configures the environment.
* **[`bin/travis/script`](../bin/travis/script)** creates the fixtures and runs the tests.

See [`example/.travis.yml`](../example/.travis.yml) for an example Travis CI configuration. Features are explained in the comments.

For more complex testing needs, ORCA commands can be invoked directly. See [Lightning Core's Travis CI configuration](https://github.com/acquia/lightning-core/blob/8.x-3.x/.travis.yml), for example.

See also [Continuous integration](understanding-orca.md#continuous-integration).

## Local installation

ORCA can also be installed and run locally for testing and development. Follow these steps to set it up:

1. Choose a directory to contain your package(s), e.g.:

    ```bash
    PARENT_DIR="$HOME/Projects"
    ```

1. Install ORCA and clone your package(s) each into the directory, e.g.:

    ```bash
    composer config --global repositories.orca vcs https://github.com/acquia/orca
    composer create-project --stability=alpha --no-dev acquia/orca "${PARENT_DIR}/orca"
    git clone git@github.com:acquia/EXAMPLE.git "${PARENT_DIR}/EXAMPLE"
    ```

Invoke ORCA from the terminal (`bin/orca`). Use the `--help` command option to learn more about the various commands or see how they're used in [`bin/travis/script`](../bin/travis/script). Use the `fixture:run-server` command to run the web server for local development.

## Designing automated tests

### Tagging/grouping

ORCA uses tags (for Behat) and groups (for PHPUnit) to determine which tests to run when, as depicted in the table below, where black indicates a test's being included and white indicates its being ignored:

| | (Default) | `orca_public` | `orca_ignore` |
| --- | :---: | :---: | :---: |
| Isolated tests (own) | :black_circle: | :black_circle: | :white_circle: |
| Integrated tests (own) | :black_circle: | :black_circle: | :white_circle: |
| Integrated tests (others') | :white_circle: | :black_circle: | :white_circle: |

The default behavior is to run a test only when the package providing it is the SUT--not when it is merely included in another package's test fixture. Any test not designated public or ignored is so treated. Such tests are referred to as "private tests". This should be considered the correct choice for most tests--particularly for features that involve little or no risk of conflict with other Acquia packages, including [isolated unit tests](http://wiki.c2.com/?UnitTestIsolation) by definition.

Public tests (`orca_public`) are _always_ run, including when testing packages other than the one providing them. For example, a public test provided by Lightning API will also be run during tests of Acquia Commerce Manager, Acquia Lift, and the rest. Public tests thus lengthen builds for _all Acquia packages_ and should be used judiciously. Reserve them for high value features with meaningful risk of being broken by other packages, and make them as fast as possible.

Ignored tests (`orca_ignore`) are "ignored" and _never_ run by ORCA. Tests should be ignored when they depend upon setup or preconditions that ORCA doesn't provide, such as a fixture with unique dependencies or a database populated by SQL dump. Once ignored, such tests can be scripted to run apart from ORCA after custom setup. See [Lightning Core's Travis CI configuration](https://github.com/acquia/lightning-core/blob/8.x-3.x/.travis.yml), for example. In practice, it should rarely be necessary to ignore a test, as most setup and teardown can be accomplished through [Behat hooks](http://behat.org/en/latest/user_guide/context/hooks.html) and [PHPUnit template methods](https://phpunit.de/manual/6.5/en/fixtures.html).

---

[README](README.md) | [Understanding ORCA](understanding-orca.md) | **Getting Started** | [Project Glossary](glossary.md) | [FAQ](faq.md) | [Contribution Guide](CONTRIBUTING.md)
