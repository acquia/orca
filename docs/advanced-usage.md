# Advanced Usage

## Altering ORCA's behavior

Various aspects of ORCA's behavior can be altered at runtime through the use of environment variables. These can be set or exported in a local terminal session or [in various ways on Travis CI](https://docs.travis-ci.com/user/environment-variables/).

### Command-line application

These affect ORCA in all contexts.

* <a name="ORCA_AMPLITUDE_API_KEY"></a>**`ORCA_AMPLITUDE_API_KEY`**: Sets the Amplitude API key for telemetry events (see also [`ORCA_TELEMETRY_ENABLE`](#ORCA_TELEMETRY_ENABLE)). For security on Travis CI, [define the variable in your repository settings](https://docs.travis-ci.com/user/environment-variables/#defining-variables-in-repository-settings).

* <a name="ORCA_AMPLITUDE_USER_ID"></a>**`ORCA_AMPLITUDE_USER_ID`**: Set the Amplitude user ID for telemetry events (see also [`ORCA_TELEMETRY_ENABLE`](#ORCA_TELEMETRY_ENABLE)). Defaults to `$ORCA_SUT_NAME:$ORCA_SUT_BRANCH`, e.g., `drupal/example:8.x-1.x`, on Travis CI.

* <a name="ORCA_FIXTURE_DIR"></a>**`ORCA_FIXTURE_DIR`**: Change the directory ORCA uses for test fixtures. Acceptable values are any valid, local directory reference, e.g., `/var/www/example`, or `../example`.

* <a name="ORCA_PACKAGES_CONFIG"></a>**`ORCA_PACKAGES_CONFIG`**: Completely replace the list of packages ORCA installs in fixtures and runs tests on. Acceptable values are any valid path to a YAML file relative to ORCA itself, e.g., `../example/tests/packages.yml`. See [`config/packages.yml`](../config/packages.yml) for an example and explanation of the schema.

* <a name="ORCA_PACKAGES_CONFIG_ALTER"></a>**`ORCA_PACKAGES_CONFIG_ALTER`**: Alter the main list of packages ORCA installs in fixtures and runs tests on (add, remove, or change packages and their properties). Acceptable values are any valid path to a YAML file relative to ORCA itself, e.g., `../example/tests/packages_alter.yml`. See [`.travis.yml`](../.travis.yml) and [`example/tests/packages_alter.yml`](../example/tests/packages_alter.yml) for an example and explanation of the schema. **Note:** This option should be used conservatively as it erodes the uniformity at the heart of ORCA's _representative_ nature.

* <a name="ORCA_PHPCS_STANDARD"></a>**`ORCA_PHPCS_STANDARD`**: Change the PHP Code Sniffer standard used by the `qa:static-analysis` and `qa:fixer` commands. Acceptable values are `AcquiaPHP`, `AcquiaDrupalStrict`, and `AcquiaDrupalTransitional`. See [Acquia Coding Standards for PHP](https://packagist.org/packages/acquia/coding-standards) for details.

* <a name="ORCA_TELEMETRY_ENABLE"></a>**`ORCA_TELEMETRY_ENABLE`**: Set to `TRUE` to enable telemetry with Amplitude. Requires [`ORCA_AMPLITUDE_API_KEY`](#ORCA_AMPLITUDE_API_KEY) and [`ORCA_AMPLITUDE_USER_ID`](#ORCA_AMPLITUDE_USER_ID) values. On Travis CI, only takes effect for cron events.

### Travis CI scripts

These affect ORCA only as invoked via the Travis CI scripts.

* <a name="ORCA_CUSTOM_FIXTURE_INIT_ARGS"></a>**`ORCA_CUSTOM_FIXTURE_INIT_ARGS`**: Add command-line arguments to `fixture:init` invocation in the [`install`](../bin/travis/install.sh) build phase of a custom job. Example:

    ```yaml
    matrix:
      include:
        - { name: "Custom job", env: ORCA_JOB=CUSTOM ORCA_CUSTOM_FIXTURE_INIT_ARGS="--profile=lightning" }
    ```

* <a name="ORCA_CUSTOM_TESTS_RUN_ARGS"></a>**`ORCA_CUSTOM_TESTS_RUN_ARGS`**: Add command-line arguments to the `qa:automated-tests` invocation in the [`script`](../bin/travis/script.sh) build phase of a custom job. Example:

    ```yaml
    matrix:
      include:
        - { name: "Custom job", env: ORCA_JOB=CUSTOM ORCA_CUSTOM_TESTS_RUN_ARGS="--sut-only" }
    ```

* <a name="ORCA_FIXTURE_PROFILE"></a>**`ORCA_FIXTURE_PROFILE`**: Change the Drupal installation profile ORCA installs in fixtures. Note: Changing this value will cause non-SUT automated tests to be skipped in all jobs to avoid failures from changing such a fundamental assumption.

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| [Getting Started](getting-started.md)
| **Advanced Usage**
| [Project Glossary](glossary.md)
| [FAQ](faq.md)
| [Contribution Guide](CONTRIBUTING.md)
