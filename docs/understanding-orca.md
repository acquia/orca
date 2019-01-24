# Understanding ORCA

1. [The basics](#the-basics)
1. [Test fixtures](#test-fixtures)
1. [Static analysis](#static-analysis)
1. [Automated tests](#automated-tests)
1. [Continuous integration](#continuous-integration)

## The basics

ORCA is a [Symfony Console](https://symfony.com/doc/current/components/console.html) application. It requires PHP, Composer, SQLite (which it uses to avoid a MySQL requirement on the host), and Git. It expects to be installed in a directory adjacent to the [system under test (SUT)](glossary.md#sut). It creates its [test fixtures](glossary.md#test-fixture) under the same parent, e.g.:

 ```
 .
 └── Projects
     ├── example     # The system under test (SUT)
     ├── orca        # ORCA itself
     └── orca-build  # The test fixture
 ```

## Test fixtures

An ORCA test fixture is comprised of a [BLT](glossary.md#blt) project with one or all Acquia software packages (as specified in [`config/packages.yml`](../config/packages.yml)) added via Composer. There are two basic kinds:

* A **standard fixture** includes and installs the SUT as well as all other Acquia packages.
* A **SUT-only fixture** includes and installs the SUT and omits all other non-required Acquia packages.

Packages are included at one of two levels of stability:

* Their **recommended, stable** versions.
* Their **development** (dev/HEAD) versions.

ORCA uses [path repositories](https://getcomposer.org/doc/05-repositories.md#path) to make Composer install the system under test (SUT) from the local directory. Merely composing the test fixtures can reveal many quality and interoperability issues.

## Static analysis

ORCA checks the SUT for low level construction defects using the following static analysis tools:

* [Composer validate](https://getcomposer.org/doc/03-cli.md#validate) checks `composer.json` files for validity and completeness.
* [PHP Parallel Lint](https://github.com/JakubOnderka/PHP-Parallel-Lint) checks PHP files for syntax errors.
* [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) checks for [PHP compatibility](https://github.com/PHPCompatibility/PHPCompatibility) with all supported language versions.

Static analysis requires no special setup of the SUT.

## Automated tests

ORCA tests for functional and behavioral correctness with [PHPUnit](glossary.md#phpunit) and [Behat](glossary.md#behat).

* An **integrated test** tests the SUT in the _presence_ of all other Acquia packages (i.e., in a standard fixture) to ensure that all packages can be added to the same codebase via Composer (preventing dependency conflicts), that there are no install time or functional conflicts between them, and prevents regressions.
* An **isolated test** tests the SUT in the _absence_ of other non-required packages (i.e., in a SUT-only fixture) to ensure that it has no undeclared dependencies on other packages and functions correctly on its own.

See [Designing automated tests](getting-started.md#designing-automated-tests).

## Continuous integration

ORCA includes out-of-the-box support for Travis CI for continuous integration. The default implementation runs five concurrent jobs per build in the following configurations:

| | No fixture | SUT-only/stable | Standard/stable | SUT-only/dev | Standard/dev |
| --- | :---: | :---: | :---: | :---: | :---: |
| SUT version | Dev | Dev | Dev | Dev | Dev |
| Drupal core version | n/a | Stable | Stable | Dev | Dev |
| Other package versions  | n/a | Stable | Stable | Dev | Dev |
| Static analysis | :black_circle: | :white_circle: | :white_circle: | :white_circle: | :white_circle: |
| Automated tests | :white_circle: | :black_circle: | :black_circle: | :black_circle: | :black_circle: |
| Allow failure | :white_circle: | :white_circle: | :white_circle: | :black_circle: | :black_circle: |

See [Configuring Travis CI](getting-started.md#configuring-travis-ci).

---

[README](README.md) | **Understanding ORCA** | [Getting Started](getting-started.md) | [Project Glossary](glossary.md) | [Contribution Guide](CONTRIBUTING.md)
