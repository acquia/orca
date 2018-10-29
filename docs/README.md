# ORCA

[![Build Status](https://travis-ci.org/acquia/orca.svg?branch=master)](https://travis-ci.org/acquia/orca)

ORCA (Official Representative Customer Application) is an internal tool for testing Acquia product modules. It ensures their cross compatibility and correct functioning by installing all of them together into a realistic, functioning, best practices Drupal build and running automated tests on them.

## Design

ORCA's guiding design principle is to use products as a customer would. It creates a [BLT](https://blt.readthedocs.io/) project and installs modules with Composer, using their latest recommended major versions. It performs no manual setup tasks that are more properly accomplished with Composer or Drupal install hooks, and it respects no documentation that is not clearly called out on a module's official (drupal.org) project page.

## Usage

### Continuous Integration

See [`examples/.travis.yml`](../examples/.travis.yml) for an example Travis CI configuration.
