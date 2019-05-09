# Advanced Usage

## Altering ORCA's behavior

Various aspects of ORCA's behavior can be altered at runtime through the use of environment variables. These can be set or exported in a local terminal session or [in various ways on Travis CI](https://docs.travis-ci.com/user/environment-variables/).

These affect ORCA in all contexts:

* <a name="ORCA_FIXTURE_DIR"></a>**`ORCA_FIXTURE_DIR`**: Change the directory ORCA uses for test fixtures. Acceptable values are any valid, local directory reference, e.g., `/var/www/example`, or `../example`.

* <a name="ORCA_PACKAGES_CONFIG"></a>**`ORCA_PACKAGES_CONFIG`**: Completely replace the list of packages ORCA installs in fixtures and runs tests on. Acceptable values are any valid path to a YAML file relative to ORCA itself, e.g., `../example/tests/packages.yml`. See [`config/packages.yml`](../config/packages.yml) for an example and explanation of the schema.

* <a name="ORCA_PACKAGES_CONFIG_ALTER"></a>**`ORCA_PACKAGES_CONFIG_ALTER`**: Alter the main list of package ORCA installs in fixtures and runs tests on (add, remove, or change packages and their properties). Acceptable values are any valid path to a YAML file relative to ORCA itself, e.g., `../example/tests/packages_alter.yml`. See [`.travis.yml`](../.travis.yml) and [`example/tests/packages_alter.yml`](../example/tests/packages_alter.yml) for an example and explanation of the schema. **Note:** This option should be used conservatively as it erodes the uniformity at the heart of ORCA's _representative_ nature.

### Custom Travis CI job

These affect ORCA only as invoked via the Travis CI custom job (i.e., `ORCA_JOB=CUSTOM`):

* <a name="ORCA_CUSTOM_FIXTURE_INIT_ARGS"></a>**`ORCA_CUSTOM_FIXTURE_INIT_ARGS`**: Add command-line arguments to `fixture:init` invocation in the [`install`](../bin/travis/install.sh) build phase of a custom job.

* <a name="ORCA_CUSTOM_TESTS_RUN_ARGS"></a>**`ORCA_CUSTOM_TESTS_RUN_ARGS`**: Add command-line arguments to the `tests:run` invocation in the [`script`](../bin/travis/script.sh) build phase of a custom job.

Example:

```yaml
matrix:
  include:
    - { name: "Custom job", env: ORCA_JOB=CUSTOM ORCA_CUSTOM_FIXTURE_INIT_ARGS="--profile=lightning" ORCA_CUSTOM_TESTS_RUN_ARGS="--sut-only" }

```

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| [Getting Started](getting-started.md)
| **Advanced Usage**
| [Project Glossary](glossary.md)
| [FAQ](faq.md)
| [Contribution Guide](CONTRIBUTING.md)
