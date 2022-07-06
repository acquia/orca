# ORCA

[![Latest Stable Version](https://poser.pugx.org/acquia/orca/v/stable)](https://packagist.org/packages/acquia/orca)
[![Total Downloads](https://poser.pugx.org/acquia/orca/downloads)](https://packagist.org/packages/acquia/orca)
[![Latest Unstable Version](https://poser.pugx.org/acquia/orca/v/unstable)](https://packagist.org/packages/acquia/orca)
[![License](https://poser.pugx.org/acquia/orca/license)](https://packagist.org/packages/acquia/orca)
[![Coverage Status](https://coveralls.io/repos/github/acquia/orca/badge.svg?branch=develop)](https://coveralls.io/github/acquia/orca?branch=develop)
[![Build Status](https://github.com/acquia/orca/actions/workflows/orca.yml/badge.svg)](https://github.com/acquia/orca/actions/workflows/orca.yml)

![ORCA Logo](images/logo-wide.png)

ORCA (Official Representative Customer Application) is a tool for testing a company's Drupal-adjacent software packages. It ensures their cross compatibility and correct functioning by installing all of them together into a realistic, functioning, best practices Drupal build and running automated tests and static code analysis on them. Its guiding design principle is to use company packages as a customer would. It installs the latest recommended versions via Composer and performs no manual setup or configuration.

## Who is it for?

ORCA is for anyone who has an interest in one or more Drupal extensions or platforms continuing to work together and wants to run automated tests in a continuous integration (CI) workflow to ensure that they do, e.g.:

* Product companies that want to ensure that their own modules continue to work together or on their platforms
* Professional services organizations that want to know ahead of time if the contrib modules they commonly use together are going to have conflicts
* Contributors that want to test together one or more modules they maintain

| What does it do? | What is the value? |
| --- | --- |
| Adds all company packages to a Drupal project via Composer, installs them and their subextensions, and runs their automated tests. | Ensures that all company packages can be added to the same codebase via Composer (prevents dependency conflicts), that there are no adverse install time or functional interactions between them, and that they have no undeclared dependencies, and prevents regressions. |
| Adds only the package under test to a Drupal project via Composer, installs it and its subextensions, and runs its automated tests. | Ensures that the package under test has no undeclared dependencies on other company packages and functions correctly on its own. |
| Performs the above tests with the recommended, stable versions of company packages, Drupal core, and Drush. | Ensures that the package under test still works with the versions of other software already released and in use and prevents releases of the package from disrupting the ecosystem. |
| Performs the above tests using the latest development versions of company packages, Drupal core, and Drush. | Ensures that the package under test will continue to work when new versions of other software are released and prevents changes in the ecosystem from breaking the package. Forces early awareness and collaboration between project teams and prevents rework and release day emergency support situations. |
| Performs the above tests using [a wide spread of Drupal core versions](understanding-orca.md#continuous-integration). | Ensures that the package under test still works on all supported releases of Drupal and will continue to work when future ones drop. |
| [Upgrades to and from various versions of Drupal core](understanding-orca.md#continuous-integration) and runs automated tests. | Ensures that the upgrade process for the package under tests works and that it continues to function correctly afterward. |
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
