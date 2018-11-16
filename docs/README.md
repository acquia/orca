# ORCA

[![Build Status](https://travis-ci.org/acquia/orca.svg?branch=master)](https://travis-ci.org/acquia/orca)

ORCA (Official Representative Customer Application) is an internal tool for testing Acquia product modules. It ensures their cross compatibility and correct functioning by installing all of them together into a realistic, functioning, best practices Drupal build and running automated tests on them.

## Design

ORCA's guiding design principle is to use products as a customer would. It creates a [BLT](https://blt.readthedocs.io/) project and installs modules with Composer, using their latest recommended major versions. It performs no manual setup or configuration.

## Features & Benefits

| What does it do? | What is the value? |
| --- | --- |
| Validate `composer.json` files | Ensures valid and complete Composer configurations |
| Lint PHP | Ensures valid PHP syntax |
| Sniff for PHP version incompatibilities | Ensures compatibility with all supported versions of PHP |
| Add all product modules to a BLT project via Composer | Ensures that all product modules can be added to the same codebase via Composer. (Prevents dependency conflicts.) |
| Install all product modules and submodules | Ensures that there are no install time conflicts between product modules |
| Run all product module automated tests (Behat and PHPUnit) | Ensures that there are no functional conflicts between product modules and prevents regressions |
| Add only the product module under test to a BLT project via Composer, install with submodules, and run automated tests | Ensures that there are no undeclared dependencies on other product modules

## Usage

### Continuous Integration

See [`examples/.travis.yml`](../examples/.travis.yml) for an example Travis CI configuration.
