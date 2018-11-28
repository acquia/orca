# ORCA

[![Build Status](https://travis-ci.org/acquia/orca.svg?branch=master)](https://travis-ci.org/acquia/orca)

ORCA (Official Representative Customer Application) is a tool for testing Acquia product modules. It ensures their cross compatibility and correct functioning by installing all of them together into a realistic, functioning, best practices Drupal build and running automated tests on them.

## Design

ORCA's guiding design principle is to use products as a customer would. It creates a [BLT](https://blt.readthedocs.io/) project and installs modules with Composer, using their latest recommended major versions. It performs no manual setup or configuration.

## Features & benefits

| What does it do? | What is the value? |
| --- | --- |
| Add all product modules to a BLT project via Composer, install them and their submodules, and run their automated tests. | Ensures that all product modules can be added to the same codebase via Composer (prevents dependency conflicts), that there are no install time conflicts between product modules, that there are no functional conflicts between product modules, and prevents regressions. |
| Add only the product module under test to a BLT project via Composer, install it and its submodules, and run its automated tests. | Ensures that the product module under test has no undeclared dependencies on other product modules and functions correctly on its own. |
| Lint PHP. | Ensures valid PHP syntax. (Prevents PHP warnings and errors.) |
| Sniff for PHP version incompatibilities. | Ensures compatibility with all supported versions of PHP. |
| Validate `composer.json` files. | Ensures valid and complete Composer configurations. |

## Usage

### Continuous integration

ORCA's primary use case is in a continuous integration (CI) workflow, running against pull requests and commits. See [`examples/.travis.yml`](../example/.travis.yml) for an example Travis CI configuration.

### Local development

ORCA can also be run locally. It requires PHP and Composer (it uses SQLite so as to avoid a MySQL requirement on the host) and expects to be installed in a directory adjacent to the module under test, e.g.:

```
.
└── Projects
    ├── example_module
    └── orca
```

Follow these steps to set it up:

1. Choose a directory to contain your module(s), e.g., `~/Projects`.
1. Clone ORCA and your module(s) into the directory.
1. Run `composer install --no-dev` within the `orca` clone.
1. Invoke the ORCA console application from the terminal: `./bin/orca`. Use the `--help` option to learn more about the various commands or see how they're used in [`bin/travis/script`](../bin/travis/script).

## Additional resources

- [Project Glossary](GLOSSARY.md)
