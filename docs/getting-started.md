# Getting Started

1. [Understanding ORCA](#understanding-orca)
1. [Continuous integration](#continuous-integration)
1. [Local installation](#local-installation)

## Understanding ORCA

ORCA is a [Symfony Console](https://symfony.com/doc/current/components/console.html) application. It requires PHP, Composer, and SQLite (which it uses to avoid a MySQL requirement on the host). It expects to be installed in a directory adjacent to the system under test (SUT). It creates its test fixtures next to them, e.g.:

 ```
 .
 └── Projects
     ├── example     # The system under test (SUT)
     ├── orca        # ORCA itself
     └── orca-build  # The test fixture
 ```

A test fixture is comprised of a BLT project with all applicable Acquia software packages (as specified in [`config/packages.yml`](../config/packages.yml)) added via Composer. ORCA uses [path repositories](https://getcomposer.org/doc/05-repositories.md#path) to make Composer install the system under test (SUT) from the local directory.

## Continuous integration

ORCA's primary use case is in a continuous integration (CI) workflow, running against pull requests and commits. See [`example/.travis.yml`](../example/.travis.yml) for an example Travis CI configuration.

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

1. Optionally set a bash alias for easier invocation, e.g.:

    ```bash
    # Temporarily, per terminal session:
    alias orca="${PARENT_DIR}/orca/bin/orca"

    # Permanently on most Macs:
    echo "alias orca=\"${PARENT_DIR}/orca/bin/orca\"" | tee -a ~/.bash_profile

    # Permanently on most Linux OSes:
    echo "alias orca=\"${PARENT_DIR}/orca/bin/orca\"" | tee -a ~/.bashrc
    ```

1. Invoke ORCA from the terminal, e.g.:

    ```bash
    # Directly:
    ${PARENT_DIR}/orca/bin/orca

    # Via bash alias:
    orca
    ```

1. See the [Command Line Reference](command-line-ref.md) or use the `--help` command option to learn more about the various commands, or see how they're used in [`bin/travis/script`](../bin/travis/script). Use the `fixture:run-server` command to run the web server for local development.
