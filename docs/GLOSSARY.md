# Project Glossary

- [Behat](#behat)
- [BLT](#blt)
- [Fixture](#test-fixture)
- [Ignored tests](#ignored-tests)
- [Integrated test](#integrated-test)
- [Isolated test](#isolated-test)
- [Non-SUT tests](#non-sut-tests)
- [ORCA](#orca)
- [PHPUnit](#phpunit)
- [Private tests](#private-tests)
- [Public tests](#public-tests)
- [Standard fixture](#standard-fixture)
- [SUT](#sut)
- [SUT tests](#sut-tests)
- [SUT-only fixture](#sut-only-fixture)
- [System Under Test](#sut)
- [Test fixture](#test-fixture)

## Behat

| 'bēhat | An open source Behavior-Driven Development framework for PHP. [[Website]](http://behat.org/) ORCA automatically runs [public](#public-tests) and [private](#private-tests) Behat stories in Acquia product modules using any `behat.yml` files found in them. See also [ignored tests](#ignored-tests).

## BLT

Build and Launch Tool: Acquia's toolset for automating Drupal 8 development, testing, and deployment. [[Website]](https://github.com/acquia/blt) Provides the foundation for an ORCA [test fixture](#test-fixture).

## Ignored tests

Automated tests that ORCA "ignores" and never runs. These are designated with an `orca_ignore` tag ([Behat](#behat)) or group ([PHPUnit](#phpunit)). Tests should be "ignored" when they depend upon setup or preconditions that ORCA doesn't provide. They can then be scripted to run without ORCA after custom setup.

## Integrated test

A test of the [SUT](#sut) in the presence of all other Acquia product modules (i.e., in a [standard fixture](#standard-fixture)). Ensures that all product modules can be added to the same codebase via Composer (prevents dependency conflicts), that there are no install time conflicts between product modules, that there are no functional conflicts between product modules, and prevents regressions.

## Isolated test

A test of the [SUT](#sut) in the absence of other non-required product modules (i.e., in a [SUT-only fixture](#sut-only-fixture)). Ensures that the product module under test has no undeclared dependencies on other product modules and functions correctly on its own.

## Non-SUT tests

Automated tests provided by Acquia projects other than the [SUT](#sut).

## ORCA

Official Representative Customer Application: a tool for testing all of Acquia's product modules together in the context of a realistic, functioning, best practices Drupal build. (You are here.)

## PHPUnit

A programmer-oriented testing framework used by Drupal. [[Website]](https://phpunit.de/) [[Drupal.org]](https://www.drupal.org/docs/8/phpunit) ORCA automatically runs [public](#public-tests) and [private](#private-tests) PHPUnit tests found in Acquia product modules. See also [ignored tests](#ignored-tests).

## Private tests

Automated tests that ORCA runs only when the module that provides them is the [SUT](#sut). Any test that is not designated [public](#public-tests) or [ignored](#ignored-tests) is automatically treated as private.

## Public tests

Automated tests that ORCA runs regardless of whether or not the module that provides them is the [SUT](#sut). These are designated with an `orca_public` tag ([Behat](#behat)) or group ([PHPUnit](#phpunit)). Public tests should be limited to those covering features at the greatest risk of being broken by the presence or action of other product modules, and they should be as fast as possible since they will be run on all other product modules' builds.

## Standard fixture

A [test fixture](#test-fixture) that includes and installs the [SUT](#sut) as well as all other Acquia product modules. See also [integrated test](#integrated-test).

## SUT

| so͞ot | System Under Test: in automated testing, the software that is being tested for correct operation. In ORCA, that means an Acquia product module.

## SUT tests

Automated tests provided by the [SUT](#sut).

## SUT-only fixture

A [test fixture](#test-fixture) that includes and installs the [SUT](#sut) and omits all other non-required Acquia product modules. See also [isolated test](#isolated-test).

## Test fixture

In automated testing, a test fixture is all the things we need to have in place in order to run a test and expect a particular outcome.<sup>[[cit.]](http://xunitpatterns.com/test%20fixture%20-%20xUnit.html)</sup> In the case of ORCA, that means a [BLT](#blt) project with Acquia product modules in place and Drupal installed.
