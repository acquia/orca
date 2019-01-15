# ORCA

[![Build Status](https://travis-ci.org/acquia/orca.svg?branch=master)](https://travis-ci.org/acquia/orca)

ORCA (Official Representative Customer Application) is a tool for testing Acquia software packages. It ensures their cross compatibility and correct functioning by installing all of them together into a realistic, functioning, best practices Drupal build and running automated tests on them. Its guiding design principle is to use Acquia packages as a customer would. It installs the latest recommended versions and performs no manual setup or configuration.

| What does it do? | What is the value? |
| --- | --- |
| Adds all Acquia packages to a BLT project via Composer, installs them and their submodules, and runs their automated tests. | Ensures that all Acquia packages can be added to the same codebase via Composer (prevents dependency conflicts), that there are no install time or functional conflicts between them, and that they have no undocumented dependencies, and prevents regressions. |
| Adds only the package under test to a BLT project via Composer, installs it and its submodules, and runs its automated tests. | Ensures that the package under test has no undeclared dependencies on other Acquia packages and functions correctly on its own. |
| Performs the above tests with the recommended, stable versions of Drupal Core and Acquia packages. | Ensures that the package under still works with the versions of other software already released and in use and prevents releases of the package from disrupting the ecosystem. |
| Performs the above tests using the latest development versions of Drupal Core and Acquia packages. | Ensures that the package under test will continue to work when new versions of other software are released and prevents changes in the ecosystem from breaking the package. Forces early awareness and collaboration between project teams and prevents rework and emergency support situations. |
| Performs static analysis of the package under test. | Ensures low level construction quality. (Prevents PHP warnings and errors, version incompatibility, etc.) |

## Documentation

* [Understanding ORCA](understanding-orca.md)
* [Getting Started](getting-started.md)
* [Project Glossary](glossary.md)
* [Contribution Guide](CONTRIBUTING.md)
