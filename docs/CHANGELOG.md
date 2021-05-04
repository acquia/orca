# Changelog

## [v3.7.1](https://github.com/acquia/orca/tree/v3.7.1) (2021-05-04)

[Full Changelog](https://github.com/acquia/orca/compare/v3.7.0...v3.7.1)

**Fixed bugs:**

- Fix Drupal core 9.3.x becoming NEXT\_MINOR\_DEV too soon [\#145](https://github.com/acquia/orca/pull/145) ([TravisCarden](https://github.com/TravisCarden))

**Merged pull requests:**

- Bump composer/composer from 2.0.10 to 2.0.13 [\#144](https://github.com/acquia/orca/pull/144) ([dependabot[bot]](https://github.com/apps/dependabot))

## [v3.7.0](https://github.com/acquia/orca/tree/v3.7.0) (2021-04-26)

[Full Changelog](https://github.com/acquia/orca/compare/v3.6.0...v3.7.0)

**Implemented enhancements:**

- Use ORCA's Composer bin for shell commands [\#143](https://github.com/acquia/orca/pull/143) ([TravisCarden](https://github.com/TravisCarden))
- Add documentation and example for integrating private repositories with Coveralls [\#140](https://github.com/acquia/orca/pull/140) ([TravisCarden](https://github.com/TravisCarden))
- Exclude MissingImport rule from PHPMD [\#133](https://github.com/acquia/orca/pull/133) ([danepowell](https://github.com/danepowell))

**Fixed bugs:**

- Prevent scripts from using the wrong Composer version on Travis CI [\#141](https://github.com/acquia/orca/pull/141) ([TravisCarden](https://github.com/TravisCarden))

**Removed:**

- Remove travis Ruby gem [\#142](https://github.com/acquia/orca/pull/142) ([TravisCarden](https://github.com/TravisCarden))

## [v3.6.0](https://github.com/acquia/orca/tree/v3.6.0) (2021-02-26)

[Full Changelog](https://github.com/acquia/orca/compare/v3.5.2...v3.6.0)

**Implemented enhancements:**

- Update versions of drupal/acquia\_connector and drupal/acquia\_search in packages.yml [\#139](https://github.com/acquia/orca/pull/139) ([japerry](https://github.com/japerry))
- Add acquia/drupal-environment-detector to packages [\#132](https://github.com/acquia/orca/pull/132) ([danepowell](https://github.com/danepowell))

**Fixed bugs:**

- Fix command not found error in before\_cache.sh [\#130](https://github.com/acquia/orca/pull/130) ([danepowell](https://github.com/danepowell))

**Merged pull requests:**

- Update Composer libraries [\#138](https://github.com/acquia/orca/pull/138) ([TravisCarden](https://github.com/TravisCarden))

## [v3.5.2](https://github.com/acquia/orca/tree/v3.5.2) (2021-02-17)

[Full Changelog](https://github.com/acquia/orca/compare/v3.5.1...v3.5.2)

**Fixed bugs:**

- Fix "next minor" jobs installing old version of Drupal core [\#137](https://github.com/acquia/orca/pull/137) ([TravisCarden](https://github.com/TravisCarden))

## [v3.5.1](https://github.com/acquia/orca/tree/v3.5.1) (2021-02-12)

[Full Changelog](https://github.com/acquia/orca/compare/v3.5.0...v3.5.1)

**Fixed bugs:**

- Fix INTEGRATED\_TEST\_ON\_CURRENT\_DEV failures [\#136](https://github.com/acquia/orca/pull/136) ([TravisCarden](https://github.com/TravisCarden))

## [v3.5.0](https://github.com/acquia/orca/tree/v3.5.0) (2021-02-11)

[Full Changelog](https://github.com/acquia/orca/compare/v3.4.0...v3.5.0)

**Implemented enhancements:**

- Completely convert to Composer 2 [\#135](https://github.com/acquia/orca/pull/135) ([TravisCarden](https://github.com/TravisCarden))

## [v3.4.0](https://github.com/acquia/orca/tree/v3.4.0) (2021-01-29)

[Full Changelog](https://github.com/acquia/orca/compare/v3.3.0...v3.4.0)

**Implemented enhancements:**

- Update "health score" \(formerly "magic number"\) logic to account for config files [\#128](https://github.com/acquia/orca/pull/128) ([TravisCarden](https://github.com/TravisCarden))

## [v3.3.0](https://github.com/acquia/orca/tree/v3.3.0) (2021-01-22)

[Full Changelog](https://github.com/acquia/orca/compare/v3.2.0...v3.3.0)

**Implemented enhancements:**

- Use Composer 2 for fixture creation [\#126](https://github.com/acquia/orca/pull/126) ([TravisCarden](https://github.com/TravisCarden))

## [v3.2.0](https://github.com/acquia/orca/tree/v3.2.0) (2021-01-14)

[Full Changelog](https://github.com/acquia/orca/compare/v3.1.2...v3.2.0)

**Breaking changes:**

- Update libraries, incl. coding standards [\#124](https://github.com/acquia/orca/pull/124) ([TravisCarden](https://github.com/TravisCarden))

**Implemented enhancements:**

- Update Drupal core version compatibility for drupal/mysql56 [\#125](https://github.com/acquia/orca/pull/125) ([TravisCarden](https://github.com/TravisCarden))

**Fixed bugs:**

- Fix test runner trying to generate code coverage regardless of environment variable [\#122](https://github.com/acquia/orca/pull/122) ([TravisCarden](https://github.com/TravisCarden))

## [v3.1.2](https://github.com/acquia/orca/tree/v3.1.2) (2020-12-14)

[Full Changelog](https://github.com/acquia/orca/compare/v3.1.1...v3.1.2)

**Fixed bugs:**

- Add workaround for "Call to undefined method ::getAnnotations\(\)" error [\#120](https://github.com/acquia/orca/pull/120) ([TravisCarden](https://github.com/TravisCarden))

## [v3.1.1](https://github.com/acquia/orca/tree/v3.1.1) (2020-12-04)

[Full Changelog](https://github.com/acquia/orca/compare/v3.1.0...v3.1.1)

**Fixed bugs:**

- Fix NEXT\_MINOR\_DEV jobs fail when there's no NEXT\_MINOR Drupal core version yet [\#119](https://github.com/acquia/orca/pull/119) ([TravisCarden](https://github.com/TravisCarden))

## [v3.1.0](https://github.com/acquia/orca/tree/v3.1.0) (2020-11-19)

[Full Changelog](https://github.com/acquia/orca/compare/v3.0.0...v3.1.0)

**Implemented enhancements:**

- Replace PHPStan with drupal-check for deprecation testing [\#117](https://github.com/acquia/orca/pull/117) ([TravisCarden](https://github.com/TravisCarden))
- Add support for PHPUnit 9 in Drupal 9.1 [\#116](https://github.com/acquia/orca/pull/116) ([TravisCarden](https://github.com/TravisCarden))

## [v3.0.0](https://github.com/acquia/orca/tree/v3.0.0) (2020-11-12)

[Full Changelog](https://github.com/acquia/orca/compare/v2.11.4...v3.0.0)

**Breaking changes:**

- Implement new CI job spread to cover more Drupal core versions and scenarios [\#115](https://github.com/acquia/orca/pull/115) ([TravisCarden](https://github.com/TravisCarden))
- Make default project template selection automatic based on core version [\#105](https://github.com/acquia/orca/pull/105) ([TravisCarden](https://github.com/TravisCarden))

**Implemented enhancements:**

- Add preflight test for a "version" value in the SUT's composer.json [\#108](https://github.com/acquia/orca/pull/108) ([TravisCarden](https://github.com/TravisCarden))
- Enable using other test coverage services than Coveralls [\#106](https://github.com/acquia/orca/pull/106) ([TravisCarden](https://github.com/TravisCarden))
- Create new 'debug:guess-version' command [\#103](https://github.com/acquia/orca/pull/103) ([TravisCarden](https://github.com/TravisCarden))
- Add support for test coverage tracking with Coveralls [\#101](https://github.com/acquia/orca/pull/101) ([japerry](https://github.com/japerry))

**Fixed bugs:**

- Fix project template tests [\#113](https://github.com/acquia/orca/pull/113) ([danepowell](https://github.com/danepowell))
- Fix broken 'fixture:init --symlink-all' option [\#102](https://github.com/acquia/orca/pull/102) ([TravisCarden](https://github.com/TravisCarden))
- Fix/move report:code-coverage command [\#99](https://github.com/acquia/orca/pull/99) ([TravisCarden](https://github.com/TravisCarden))
- Set Node.js to a version compatible with Drupal 9 + Nightwatch.js. [\#94](https://github.com/acquia/orca/pull/94) ([TravisCarden](https://github.com/TravisCarden))
- Fix broken support for absolute package paths [\#93](https://github.com/acquia/orca/pull/93) ([TravisCarden](https://github.com/TravisCarden))

**Removed:**

- Remove CUSTOM ORCA\_JOB [\#114](https://github.com/acquia/orca/pull/114) ([TravisCarden](https://github.com/TravisCarden))

**Merged pull requests:**

- Update version of acquia/coding-standards [\#111](https://github.com/acquia/orca/pull/111) ([TravisCarden](https://github.com/TravisCarden))



\* *This Changelog was automatically generated by [github_changelog_generator](https://github.com/github-changelog-generator/github-changelog-generator)*
