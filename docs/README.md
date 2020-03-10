# ORCA

[![Latest Stable Version](https://poser.pugx.org/acquia/orca/v/stable)](https://packagist.org/packages/acquia/orca)
[![Total Downloads](https://poser.pugx.org/acquia/orca/downloads)](https://packagist.org/packages/acquia/orca)
[![Latest Unstable Version](https://poser.pugx.org/acquia/orca/v/unstable)](https://packagist.org/packages/acquia/orca)
[![License](https://poser.pugx.org/acquia/orca/license)](https://packagist.org/packages/acquia/orca)
[![Build Status](https://travis-ci.org/acquia/orca.svg?branch=master)](https://travis-ci.org/acquia/orca)

ORCA (Official Representative Customer Application) is a tool for testing a company's Drupal-adjacent software packages. It ensures their cross compatibility and correct functioning by installing all of them together into a realistic, functioning, best practices Drupal build and running automated tests and static code analysis on them. Its guiding design principle is to use company packages as a customer would. It installs the latest recommended versions via Composer and performs no manual setup or configuration.

| What does it do? | What is the value? |
| --- | --- |
| Adds all company packages to a BLT project via Composer, installs them and their subextensions, and runs their automated tests. | Ensures that all company packages can be added to the same codebase via Composer (prevents dependency conflicts), that there are no adverse install time or functional interactions between them, and that they have no undeclared dependencies, and prevents regressions. |
| Adds only the package under test to a BLT project via Composer, installs it and its subextensions, and runs its automated tests. | Ensures that the package under test has no undeclared dependencies on other company packages and functions correctly on its own. |
| Performs the above tests with the recommended, stable versions of company packages and Drush. | Ensures that the package under test still works with the versions of other software already released and in use and prevents releases of the package from disrupting the ecosystem. |
| Performs the above tests using the latest development versions of company packages and Drush. | Ensures that the package under test will continue to work when new versions of other software are released and prevents changes in the ecosystem from breaking the package. Forces early awareness and collaboration between project teams and prevents rework and release day emergency support situations. |
| Performs the above tests using a threefold spread of Drupal core versions: the previous minor release, the current supported release, and the next minor dev version. | Ensures that the package under test still works with both supported releases of Drupal and will continue to work when the next one drops. |
| Performs static analysis of the package under test. | Ensures low level construction quality. (Prevents PHP warnings and errors, version incompatibility, etc.) |

See [Continuous integration](understanding-orca.md#Continuous-integration) for exact details.

## Documentation

* [Understanding ORCA](understanding-orca.md)
* [Getting Started](getting-started.md)
* [CLI Commands](commands.md)
* [Advanced Usage](advanced-usage.md)
* [Project Glossary](glossary.md)
* [FAQ](faq.md)
* [Contribution Guide](CONTRIBUTING.md)
