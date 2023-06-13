# Project Glossary

* [Acquia CMS](#acquia-cms)
* [Bare fixture](#bare-fixture)
* [Fixture](#test-fixture)
* [Ignored tests](#ignored-tests)
* [Integrated test](#integrated-test)
* [Isolated test](#isolated-test)
* [Isolated unit tests](#isolated-unit-tests)
* [Nightwatch.js](#nightwatchjs)
* [Non-SUT tests](#non-sut-tests)
* [ORCA](#orca)
* [ORCA internals](#orca-internals)
* [PHPUnit](#phpunit)
* [Plumbing interface](#orca-internals)
* [Porcelain interface](#orca-internals)
* [Private tests](#private-tests)
* [Project template](#project-template)
* [Public tests](#public-tests)
* [Standard fixture](#standard-fixture)
* [SUT](#sut)
* [SUT tests](#sut-tests)
* [SUT-only fixture](#sut-only-fixture)
* [System Under Test](#sut)
* [Test fixture](#test-fixture)

## Acquia CMS

[Acquia CMS](https://www.drupal.org/project/acquia_cms) (`drupal/acquia_cms`) is Acquia's opinionated Drupal distribution for running low-code websites on the Acquia hosting platform. It is included in ORCA [test fixtures](#test-fixture) by default by way of [Drupal Recommended Project](#project-template). Cf. [Why do I get version conflicts with drupal/acquia_cms?](faq.md#why-do-i-get-version-conflicts-with-drupalacquia_cms).

## Bare fixture

A [test fixture](#test-fixture) that neither includes nor installs any [SUT](#sut) or other company packages.

## Ignored tests

Automated tests that ORCA "ignores" and never runs. These are designated with an `orca_ignore` group for [PHPUnit](#phpunit). Tests should be "ignored" when they depend upon setup or preconditions that ORCA doesn't provide. They can then be scripted to run without ORCA after custom setup. [Read more in Running automated tests: Tagging/grouping.](getting-started.md#tagginggrouping)

## Integrated test

A test of the [SUT](#sut) in the presence of all other company packages (i.e., in a [standard fixture](#standard-fixture)). [Read more in Understanding ORCA.](understanding-orca.md#automated-tests)

## Isolated test

A test of the [SUT](#sut) in the absence of other non-required packages (i.e., in a [SUT-only fixture](#sut-only-fixture)). [Read more in Understanding ORCA.](understanding-orca.md#automated-tests)

## Isolated unit tests

Unit tests that run "in isolation", i.e., separate and apart from the application and all other units. See [Unit Test Isolation](http://wiki.c2.com/?UnitTestIsolation).

## Nightwatch.js

An integrated, end-to-end testing solution for web applications and websites. [[Website]](https://nightwatchjs.org/) ORCA automatically runs Nightwatch tests in the SUT tagged with the package's machine name.

## Non-SUT tests

Automated tests provided by company packages other than the [SUT](#sut).

## ORCA

Official Representative Customer Application: a tool for testing all of a company's software packages together in the context of a realistic, functioning, best practices Drupal build. (You are here.)

## ORCA internals

ORCA may be thought of as providing two interfaces: a "porcelain" interface comprised of easy-to-use CI scripts that encapsulates high level testing policy and covers the 90% use case, and a "plumbing" interface consisting of a highly flexible command line application that exposes low level options and functionality. [Read more in Getting Started.](getting-started.md)

## PHPUnit

A programmer-oriented testing framework used by Drupal. [[Website]](https://phpunit.de/) [[Drupal.org]](https://www.drupal.org/docs/8/phpunit) ORCA automatically runs [public](#public-tests) and [private](#private-tests) PHPUnit tests found in company packages. See also [ignored tests](#ignored-tests).

## Private tests

Automated tests that ORCA runs only when the package that provides them is the [SUT](#sut). Any test that is not designated [public](#public-tests) or [ignored](#ignored-tests) is automatically treated as private. [Read more in Running automated tests: Tagging/grouping.](getting-started.md#tagginggrouping)

## Project template

A project template is a way to use Composer to create new projects from an existing package. See [composer create-project](https://getcomposer.org/doc/03-cli.md#create-project). This is the preferred way to manage Drupal and all dependencies (modules, themes, libraries). See [Using Composer to Install Drupal and Manage Dependencies](https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies). Acquia provides two project templates:

* <a name="acquia-drupal-recommended-project"</a>[**Acquia Drupal Recommended Project**](https://github.com/acquia/drupal-recommended-project) (`acquia/drupal-recommended-project`) is a project template providing a great out-of-the-box experience for new Drupal 9 projects hosted on Acquia.
* <a name="acquia-drupal-minimal-project"</a>[**Acquia Drupal Minimal Project**](https://github.com/acquia/drupal-minimal-project) (`acquia/drupal-minimal-project`) provides a minimal Drupal application to be hosted on Acquia.

By default, ORCA uses Acquia Drupal Recommended Project to create [test fixtures](#test-fixture). This behavior can be changed using the `--project-template` option of the [`fixture:init`](advanced-usage.md#fixtureinit) Console command like this, for example:

   ```shell
   orca fixture:init --project-template=acquia/drupal-minimal-project
   ```

On GitHub Actions, it can be changed via the [`ORCA_FIXTURE_PROJECT_TEMPLATE`](advanced-usage.md#ORCA_FIXTURE_PROJECT_TEMPLATE) environment variable in your `orca.yml` like this:

   ```yaml
   jobs:
     build:
       env:
        ORCA_FIXTURE_PROJECT_TEMPLATE: acquia/drupal-minimal-project
   ```

## Public tests

Automated tests that ORCA runs regardless of whether or not the package that provides them is the [SUT](#sut). These are designated with an `orca_public` group for [PHPUnit](#phpunit). Public tests should be limited to those covering features at the greatest risk of being broken by the presence or action of other company packages, and they should be as fast as possible since they will be run on all other company packages' builds. [Read more in Running automated tests: Tagging/grouping.](getting-started.md#tagginggrouping)

## Standard fixture

A [test fixture](#test-fixture) that includes and installs the [SUT](#sut) as well as all other company packages. See also [integrated test](#integrated-test).

## SUT

| so͞ot | System Under Test: in automated testing, the software that is being tested for correct operation. In ORCA, that means a company package.

## SUT tests

Automated tests provided by the [SUT](#sut).

## SUT-only fixture

A [test fixture](#test-fixture) that includes and installs the [SUT](#sut) and omits all other non-required company packages. See also [isolated test](#isolated-test).

## Test fixture

In automated testing, a test fixture is all the things we need to have in place in order to run a test and expect a particular outcome.<sup>[[cit.]](http://xunitpatterns.com/test%20fixture%20-%20xUnit.html)</sup> In the case of ORCA, that means a [project template](#project-template) with all applicable company software packages in place and Drupal installed. [Read more in Understanding ORCA.](understanding-orca.md#test-fixtures)

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| [Getting Started](getting-started.md)
| [CLI Commands](commands.md)
| [Advanced Usage](advanced-usage.md)
| **Project Glossary**
| [FAQ](faq.md)
| [Contribution Guide](CONTRIBUTING.md)
