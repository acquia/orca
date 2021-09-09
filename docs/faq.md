# Frequently Asked Questions

* [Composer](#composer)
* [Coveralls](#coveralls)
* [Drupal](#drupal)

## Composer

### Why doesn't ORCA install my dev dependencies?

ORCA doesn't install dev dependencies because Composer provides no means of doing so--Composer only recognizes dev dependencies of the root package, which in ORCA's case is the BLT project. But even if technically possible it would be undesirable on account of the resulting strict but indirect dependency it would create between product teams since a standard fixture would then require the dev dependencies of all packages at once. Two main approaches are available to deal with this limitation:

1. For conditional functionality (i.e., behavior that manifests only in the presence of dependencies, such as optional Drupal modules), move the functionality into a submodule and require those conditions in its `composer.json`. ORCA will automatically discover the submodule and install the dependencies with Composer. This design may be considered an expression of [the Common Reuse Principle (CRP) of package cohesion](https://en.wikipedia.org/wiki/Package_principles#Principles_of_package_cohesion), in which sense the "no dev dependencies" limitation actually encourages good system design.

1. Invoke `composer require` manually in your `.travis.yml` (or in a script called by it) to add the dependency after it's created, e.g.:

   ```yaml
   install:
     - ../orca/bin/common/install.sh

     # Add the physical dependency to the codebase.
     - cd "$TRAVIS_BUILD_DIR/../orca-build"
     - composer require drupal/dev_only_dependency

     # Install Drupal modules and themes.
     - cd docroot
     - ../vendor/bin/drush pm:enable -y dev_only_dependency

     # Backup the fixture state for ORCA's automatic resets between tests.
     - ../../orca/bin/orca fixture:backup -f
   ```

1. Simply "ignore" tests with unique dependencies and run them apart from ORCA. See [Running automated tests](getting-started.md#tagginggrouping).

### Why do I get version conflicts with drupal/acquia_cms?

As an opinionated [project template](#project-template), [Acquia CMS](glossary.md#acquia-cms) (`drupal/acquia_cms`) has very tight version constraints that can conflict with your dependencies as depicted in this example error:

   ```
   Problem 1
     - acquia/acquia_cms[v1.2-rc1, ..., 1.2.5.x-dev] require drupal/acquia_connector ^3 -> satisfiable by drupal/acquia_connector[dev-3.x, 3.0.0-rc1, ..., 3.x-dev (alias of dev-3.x)] from composer repo (https://packages.drupal.org/8) but drupal/acquia_connector[dev-8.x-1.x, 1.x-dev (alias of dev-8.x-1.x)] from path repo (/home/travis/build/acquia/acquia_connector) has higher repository priority. The packages with higher priority do not match your constraint and are therefore not installable. See https://getcomposer.org/repoprio for details and assistance.
   ```

Acquia CMS is included in ORCA [test fixtures](glossary.md#test-fixture) by default by way of a requirement in [Acquia Drupal Recommended Project](glossary.md#acquia-drupal-recommended-project). If your package or one of its version branches is not meant to support Acquia CMS, you should use a different [project template](glossary.md#project-template). Add the following to your `.travis.yml` to do so on Travis CI:

   ```yaml
   env:
     global:
       - ORCA_FIXTURE_PROJECT_TEMPLATE=acquia/drupal-minimal-project
   ```

## Coveralls

### How do I add my GitHub repository to Coveralls?

1. [Sign in to Coveralls](https://coveralls.io/authorize/github) with your GitHub account.
1. Click the "Add Repos" menu link.
1. Click the !["Add your repository to Coveralls"](images/coveralls-button.png) button next to your repository.

### What if my GitHub repository is private?

1. Set the `COVERALLS_REPO_TOKEN` environment variable [in your Travis CI repository settings](https://docs.travis-ci.com/user/environment-variables/#defining-variables-in-repository-settings) to the [secret repo token](https://docs.coveralls.io/api-introduction#referencing-a-repository) found at the bottom of your repository's page on Coveralls.
1. Copy [`example/.coveralls.yml`](../example/.coveralls.yml) into your repository root and uncomment the indicated line.

## Drupal

### Why doesn't ORCA enable my submodule/subtheme?

ORCA automatically discovers and enables any subextension that satisfies the following criteria:

* It exists in a subdirectory of a present package (other than `tests`).
* It has a valid `composer.json`...
    * with a `type` value of `drupal-module` or `drupal-theme`...
    * and a vendor name of "drupal", i.e., a `name` value beginning with `drupal/`.
* It has a corresponding `.info.yml` file (e.g., for a Composer `name` of `drupal/example`, `example.info.yml`).
* It doesn't explicitly opt out. See [How can I prevent ORCA from enabling my submodule/subtheme?](#how-can-i-prevent-orca-from-enabling-my-submodulesubtheme).

Cf. [`\Acquia\Orca\Fixture\subextensionManager`](../src/Fixture/subextensionManager.php).

### How can I prevent ORCA from enabling my submodule/subtheme?

To prevent ORCA from enabling a subextension, add an `extra.orca.enable` value of `TRUE` to its `composer.json`, e.g.:

```json
{
    "name": "drupal/example_submodule",
    "type": "drupal-module",
    "extra": {
        "orca": {
            "enable": false
        }
    }
}

```

Cf. [Why doesn't ORCA enable my submodule/subtheme?](#why-doesnt-orca-enable-my-submodulesubtheme).

---

[README](README.md)
| [Understanding ORCA](understanding-orca.md)
| [Getting Started](getting-started.md)
| [CLI Commands](commands.md)
| [Advanced Usage](advanced-usage.md)
| [Project Glossary](glossary.md)
| **FAQ**
| [Contribution Guide](CONTRIBUTING.md)
