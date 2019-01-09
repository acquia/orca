# Getting Started

1. [Continuous integration](#continuous-integration)
1. [Local installation](#local-installation)
1. [Designing automated tests](#designing-automated-tests)

## Continuous integration

ORCA's primary use case is in a continuous integration (CI) workflow, running against pull requests and commits. It provides two scripts corresponding to Travis CI hooks:

* **[`bin/travis/install`](../bin/travis/install)** configures the environment and installs ORCA.
* **[`bin/travis/script`](../bin/travis/script)** creates the fixtures and runs the tests.

See [`example/.travis.yml`](../example/.travis.yml) for an example Travis CI configuration. Features are explained in the comments.

For more complex testing needs, ORCA commands can be invoked directly. See [Lightning Core's Travis CI configuration](https://github.com/acquia/lightning-core/blob/8.x-3.x/.travis.yml), for example.

## Local installation

ORCA can also be installed and run locally for testing and development. Follow these steps to set it up:

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

Invoke ORCA from the terminal (`bin/orca`). Use the `--help` command option to learn more about the various commands or see how they're used in [`bin/travis/script`](../bin/travis/script). Use the `fixture:run-server` command to run the web server for local development.

## Designing automated tests

| Fixture | Tests run |
| --- | --- |
| Standard fixture (integrated test) | SUT ORCA tests (`-orca_ignore`), non-SUT public ORCA tests (`-orca_ignore, +orca_public`) |
| SUT-only fixture (isolated test) | SUT ORCA tests (`-orca_ignore`) |
| Outside ORCA | SUT non-ORCA tests (`+orca_ignore`) |
