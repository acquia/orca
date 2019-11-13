# Frequently Asked Questions

* [Composer](#composer)
* [Drupal](#drupal)

## Composer

### Why doesn't ORCA install my dev dependencies?

ORCA doesn't install dev dependencies because Composer provides no means of doing so--Composer only recognizes dev dependencies of the root package, which in ORCA's case is the BLT project. But even if technically possible it would be undesirable on account of the resulting strict but indirect dependency it would create between product teams since a standard fixture would then require the dev dependencies of all packages at once. Two main approaches are available to deal with this limitation:

1. For conditional functionality (i.e., behavior that manifests only in the presence of dependencies, such as optional Drupal modules), move the functionality into a submodule and require those conditions in its `composer.json`. ORCA will automatically discover the submodule and install the dependencies with Composer. This design may be considered an expression of [the Common Reuse Principle (CRP) of package cohesion](https://en.wikipedia.org/wiki/Package_principles#Principles_of_package_cohesion), in which sense the "no dev dependencies" limitation actually encourages good system design.

1. Simply "ignore" tests with unique dependencies and run them apart from ORCA. See [Designing automated tests](getting-started.md#tagginggrouping).

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
