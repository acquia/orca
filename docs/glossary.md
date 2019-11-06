# Project Glossary

* [Bare fixture](#bare-fixture)
* [Behat](#behat)
* [BLT](#blt)
* [Fixture](#test-fixture)
* [Ignored tests](#ignored-tests)
* [Integrated test](#integrated-test)
* [Isolated test](#isolated-test)
* [Isolated unit tests](#isolated-unit-tests)
* [Non-SUT tests](#non-sut-tests)
* [ORCA](#orca)
* [ORCA internals](#orca-internals)
* [PHPUnit](#phpunit)
* [Plumbing interface](#orca-internals)
* [Porcelain interface](#orca-internals)
* [Private tests](#private-tests)
* [Public tests](#public-tests)
* [Standard fixture](#standard-fixture)
* [SUT](#sut)
* [SUT tests](#sut-tests)
* [SUT-only fixture](#sut-only-fixture)
* [System Under Test](#sut)
* [Test fixture](#test-fixture)

## Bare fixture

A [test fixture](#test-fixture) that neither includes nor installs any [SUT](#sut) or other company packages (except of course for BLT which is the foundation of all fixtures).

## Behat

| 'bēhat | An open source Behavior-Driven Development framework for PHP. [[Website]](http://behat.org/) ORCA automatically runs [public](#public-tests) and [private](#private-tests) Behat tests in the SUT only using a `behat.yml` files found in its root directory if present. See also [ignored tests](#ignored-tests).

## BLT

Build and Launch Tool: Acquia's toolset for automating Drupal 8 development, testing, and deployment. [[Website]](https://github.com/acquia/blt) Provides the foundation for an ORCA [test fixture](#test-fixture).

## Ignored tests

Automated tests that ORCA "ignores" and never runs. These are designated with an `orca_ignore` tag ([Behat](#behat)) or group ([PHPUnit](#phpunit)). Tests should be "ignored" when they depend upon setup or preconditions that ORCA doesn't provide. They can then be scripted to run without ORCA after custom setup. [Read more in Designing automated tests: Tagging/grouping.](getting-started.md#tagginggrouping)

## Integrated test

A test of the [SUT](#sut) in the presence of all other company packages (i.e., in a [standard fixture](#standard-fixture)). [Read more in Understanding ORCA.](understanding-orca.md#automated-tests)

## Isolated test

A test of the [SUT](#sut) in the absence of other non-required packages (i.e., in a [SUT-only fixture](#sut-only-fixture)). [Read more in Understanding ORCA.](understanding-orca.md#automated-tests)

## Isolated unit tests

Unit tests that run "in isolation", i.e., separate and apart from the application and all other units. See [Unit Test Isolation](http://wiki.c2.com/?UnitTestIsolation).

## Non-SUT tests

Automated tests provided by company packages other than the [SUT](#sut).

## ORCA

Official Representative Customer Application: a tool for testing all of a company's software packages together in the context of a realistic, functioning, best practices Drupal build. (You are here.)

## ORCA internals

ORCA may be thought of as providing two interfaces: a "porcelain" interface comprised of easy-to-use CI scripts that encapsulates high level testing policy and covers the 90% use case, and a "plumbing" interface consisting of a highly flexible command line application that exposes low level options and functionality. [Read more in Getting Started.](getting-started.md)

## PHPUnit

A programmer-oriented testing framework used by Drupal. [[Website]](https://phpunit.de/) [[Drupal.org]](https://www.drupal.org/docs/8/phpunit) ORCA automatically runs [public](#public-tests) and [private](#private-tests) PHPUnit tests found in company packages. See also [ignored tests](#ignored-tests).

## Private tests

Automated tests that ORCA runs only when the package that provides them is the [SUT](#sut). Any test that is not designated [public](#public-tests) or [ignored](#ignored-tests) is automatically treated as private. [Read more in Designing automated tests: Tagging/grouping.](getting-started.md#tagginggrouping)

## Public tests

Automated tests that ORCA runs regardless of whether or not the package that provides them is the [SUT](#sut). These are designated with an `orca_public` tag ([Behat](#behat)) or group ([PHPUnit](#phpunit)). Public tests should be limited to those covering features at the greatest risk of being broken by the presence or action of other company packages, and they should be as fast as possible since they will be run on all other company packages' builds. [Read more in Designing automated tests: Tagging/grouping.](getting-started.md#tagginggrouping)

## Standard fixture

A [test fixture](#test-fixture) that includes and installs the [SUT](#sut) as well as all other company packages. See also [integrated test](#integrated-test).

## SUT

| so͞ot | System Under Test: in automated testing, the software that is being tested for correct operation. In ORCA, that means a company package.

## SUT tests

Automated tests provided by the [SUT](#sut).

## SUT-only fixture

A [test fixture](#test-fixture) that includes and installs the [SUT](#sut) and omits all other non-required company packages. See also [isolated test](#isolated-test).

## Test fixture

In automated testing, a test fixture is all the things we need to have in place in order to run a test and expect a particular outcome.<sup>[[cit.]](http://xunitpatterns.com/test%20fixture%20-%20xUnit.html)</sup> In the case of ORCA, that means a [BLT](#blt) project with all applicable company software packages in place and Drupal installed. [Read more in Understanding ORCA.](understanding-orca.md#test-fixtures)

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| [Getting Started](getting-started.md)
| [Advanced Usage](advanced-usage.md)
| **Project Glossary**
| [FAQ](faq.md)
| [Contribution Guide](CONTRIBUTING.md)
