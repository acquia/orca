# Frequently Asked Questions

## Why doesn't ORCA install my dev dependencies?

ORCA doesn't install dev dependencies because Composer provides no means of doing so--Composer only recognizes dev dependencies of the root package, which in ORCA's case is the BLT project. But even if technically possible it would be undesirable on account of the resulting strict but indirect dependency it would create between product teams since a standard fixture would then require the dev dependencies of all packages at once. Two main approaches are available to deal with this limitation:

1. For conditional functionality (i.e., behavior that manifests only in the presence of dependencies, such as optional Drupal modules), move the functionality into a submodule with a hard dependency on those conditions. ORCA will automatically install the dependencies with Composer and enable them in Drupal. This design may be considered an expression of [the Common Reuse Principle (CRP) of package cohesion](https://en.wikipedia.org/wiki/Package_principles#Principles_of_package_cohesion), in which sense the "no dev dependencies" limitation actually encourages good system design.

1. Simply "ignore" tests with unique dependencies and run them apart from ORCA. See [Designing automated tests](getting-started.md#tagginggrouping).

[README](README.md) | [Understanding ORCA](understanding-orca.md) | [Getting Started](getting-started.md) | [Project Glossary](glossary.md) | **FAQ** | [Contribution Guide](CONTRIBUTING.md)
