# Project Glossary

## Behat

| 'bēhat | An open source Behavior-Driven Development framework for PHP. [[Website]](http://behat.org/) ORCA automatically runs Behat tests with the `orca_public` tag in Acquia product modules using any `behat.yml` files found in them.

## BLT

Build and Launch Tool: Acquia's toolset for automating Drupal 8 development, testing, and deployment. [[Website]](https://github.com/acquia/blt) Provides the foundation for an ORCA [test fixture](#test-fixture).

## Fixture

See [test fixture](#test-fixture).

## Integrated test

A test of the [SUT](#sut) in the presence of all other Acquia product modules (i.e., in a [standard fixture](#standard-fixture)). Ensures that all product modules can be added to the same codebase via Composer (prevents dependency conflicts), that there are no install time conflicts between product modules, that there are no functional conflicts between product modules, and prevents regressions.

## Isolated test

A test of the [SUT](#sut) in the absence of other non-required product modules (i.e., in a [SUT-only fixture](#sut-only-fixture)). Ensures that the product module under test has no undeclared dependencies on other product modules and functions correctly on its own.

## ORCA

Official Representative Customer Application: a tool for testing all of Acquia's product modules together in the context of a realistic, functioning, best practices Drupal build. (You are here.)

## PHPUnit

A programmer-oriented testing framework used by Drupal. [[Website]](https://phpunit.de/) [[Drupal.org]](https://www.drupal.org/docs/8/phpunit) ORCA automatically runs any PHPUnit tests in the `orca_public` group found in Acquia product modules.

## Standard fixture

A [test fixture](#test-fixture) that includes and installs the system under test ([SUT](#sut)) as well as all other Acquia product modules. See also [integrated test](#integrated-test).

## SUT

| so͞ot | System Under Test: in automated testing, the software that is being tested for correct operation. In ORCA, that means an Acquia product module.

### SUT-only fixture

A [test fixture](#test-fixture) that includes and installs the system under test ([SUT](#sut)) and omits all other non-required Acquia product modules. See also [isolated test](#isolated-test).

## Test fixture

In automated testing, the set of conditions required to run a test and expect a certain outcome. In ORCA, that means the [BLT](#blt) project, Acquia product module(s), and Drupal installation.
