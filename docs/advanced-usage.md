# Advanced Usage

## Altering ORCA's behavior

Various aspects of ORCA's behavior can be altered at runtime through the use of environment variables. These can be set or exported in a local terminal session or [in various ways on GitHub Actions](https://docs.github.com/en/actions/learn-github-actions/variables#about-variables).

### Command-line application

These affect ORCA in all contexts.

* <a name="ORCA_COVERAGE_COBERTURA"></a>**`ORCA_COVERAGE_COBERTURA`**: Change the path where ORCA saves the PHPUnit test coverage Clover XML file.

* <a name="ORCA_COVERAGE_ENABLE"></a>**`ORCA_COVERAGE_ENABLE`**: Set to `TRUE` to generate test coverage data . The resulting Clover file will be saved at the location set in [`ORCA_COVERAGE_COBERTURA`](#ORCA_COVERAGE_COBERTURA). Test coverage generation greatly increases build times, so only enable it on one job--all that makes sense anyway.

* <a name="ORCA_FIXTURE_DIR"></a>**`ORCA_FIXTURE_DIR`**: Change the directory ORCA uses for test fixtures. Acceptable values are any valid, local directory reference, e.g., `/var/www/example`, or `../example`.

* <a name="ORCA_PACKAGES_CONFIG"></a>**`ORCA_PACKAGES_CONFIG`**: Completely replace the list of packages ORCA installs in fixtures and runs tests on. Acceptable values are any valid path to a YAML file relative to ORCA itself, e.g., `../example/tests/packages.yml`. See [`config/packages.yml`](../config/packages.yml) for an example and explanation of the schema.

* <a name="ORCA_PACKAGES_CONFIG_ALTER"></a>**`ORCA_PACKAGES_CONFIG_ALTER`**: Alter the main list of packages ORCA installs in fixtures and runs tests on (add, remove, or change packages and their properties). Acceptable values are any valid path to a YAML file relative to ORCA itself, e.g., `../example/tests/packages_alter.yml`. See [`orca.yml`](../.github/workflows/orca.yml) and [`example/tests/packages_alter.yml`](../example/tests/packages_alter.yml) for an example and explanation of the schema. **Note:** This option should be used conservatively as it erodes the uniformity at the heart of ORCA's _representative_ nature.

* <a name="ORCA_PHPCS_STANDARD"></a>**`ORCA_PHPCS_STANDARD`**: Change the PHP Code Sniffer standard used by the `qa:static-analysis` and `qa:fixer` commands. Acceptable values are `AcquiaPHP`, `AcquiaDrupalStrict`, and `AcquiaDrupalTransitional`. See [Acquia Coding Standards for PHP](https://packagist.org/packages/acquia/coding-standards) for details.

* <a name="ORCA_SUT_DIR"></a>**`ORCA_SUT_DIR`**: Change the path where ORCA looks for the SUT. Accepted values are any valid directory path, e.g., `/var/www/example`, or `../example`.

* <a name="ORCA_TELEMETRY_ENABLE"></a>**`ORCA_TELEMETRY_ENABLE`**: Set to `TRUE` to enable telemetry.

### CI scripts

These affect ORCA only as invoked via the CI scripts.

* <a name="ORCA_FIXTURE_PROFILE"></a>**`ORCA_FIXTURE_PROFILE`**: Change the Drupal installation profile ORCA installs in fixtures. Note: Changing this value will cause non-SUT automated tests to be skipped in all jobs to avoid failures from changing such a fundamental assumption.

* <a name="ORCA_FIXTURE_PROJECT_TEMPLATE"></a>**`ORCA_FIXTURE_PROJECT_TEMPLATE`**: Change the Composer project template used to create the fixture.

## Adding and customizing jobs

For special testing needs, custom jobs can be added and existing ones modified through the addition of scripts to your `.orca.yml`, e.g.:

   ```yaml
   before_script:
     - ../orca/bin/ci/before_script.sh
     # Your custom script:
     - ./bin/ci/before_script.sh
   ```

See [the example script](https://github.com/acquia/orca/blob/main/example/bin/ci/example.sh) for more details.

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| [Getting Started](getting-started.md)
| [CLI Commands](commands.md)
| **Advanced Usage**
| [Project Glossary](glossary.md)
| [FAQ](faq.md)
| [Contribution Guide](CONTRIBUTING.md)
