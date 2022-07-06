<?php

namespace Acquia\Orca\Options;

use Acquia\Orca\Domain\Composer\ComposerFacade;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Composer\Semver\VersionParser;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Encapsulates fixture options.
 */
class FixtureOptions {

  /**
   * The resolved core version.
   *
   * @var string
   */
  private $coreResolved = '';

  /**
   * The Drupal Core version finder.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver
   */
  private $drupalCoreVersionResolver;

  /**
   * The resolved options.
   *
   * @var array
   */
  private $options;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The raw options.
   *
   * @var array
   */
  private $rawOptions;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_resolver
   *   The Drupal core version resolver.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   * @param array $raw_options
   *   The options.
   *
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   */
  public function __construct(DrupalCoreVersionResolver $drupal_core_version_resolver, PackageManager $package_manager, array $raw_options) {
    $this->drupalCoreVersionResolver = $drupal_core_version_resolver;
    $this->packageManager = $package_manager;
    $this->rawOptions = $raw_options;
    $this->resolve($raw_options);
    $this->validate();
  }

  /**
   * Resolves the given options.
   *
   * @param array $options
   *   The options to resolve.
   *
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   */
  private function resolve(array $options): void {
    try {
      $this->options = (new OptionsResolver())
        // Set defined.
        ->setDefined([
          'bare',
          'core',
          'dev',
          'force',
          'ignore-patch-failure',
          'no-site-install',
          'no-sqlite',
          'prefer-source',
          'profile',
          'project-template',
          'sut',
          'sut-only',
          'symlink-all',
        ])
        // Set defaults.
        ->setDefaults([
          'bare' => FALSE,
          'core' => NULL,
          'dev' => FALSE,
          'force' => FALSE,
          'ignore-patch-failure' => FALSE,
          'no-site-install' => FALSE,
          'no-sqlite' => FALSE,
          'prefer-source' => FALSE,
          'profile' => NULL,
          'project-template' => NULL,
          'sut' => NULL,
          'sut-only' => FALSE,
          'symlink-all' => FALSE,
        ])
        // Set allowed types.
        ->setAllowedTypes('bare', ['boolean'])
        ->setAllowedTypes('core', ['string', 'null'])
        ->setAllowedTypes('dev', ['boolean'])
        ->setAllowedTypes('force', ['boolean'])
        ->setAllowedTypes('ignore-patch-failure', ['boolean'])
        ->setAllowedTypes('no-site-install', ['boolean'])
        ->setAllowedTypes('no-sqlite', ['boolean'])
        ->setAllowedTypes('prefer-source', ['boolean'])
        ->setAllowedTypes('profile', ['string', 'null'])
        ->setAllowedTypes('project-template', ['string', 'null'])
        ->setAllowedTypes('sut', ['string', 'null'])
        ->setAllowedTypes('sut-only', ['boolean'])
        ->setAllowedTypes('symlink-all', ['boolean'])
        // Set allowed values.
        ->setAllowedValues('core', $this->isValidCoreValue())
        ->setAllowedValues('profile', $this->isValidProfileValue())
        ->setAllowedValues('project-template', $this->isValidProjectTemplateValue())
        ->setAllowedValues('sut', $this->isValidSutValue())
        // Resolve.
        ->resolve($options);
    }
    catch (UndefinedOptionsException | InvalidOptionsException $e) {
      throw new OrcaInvalidArgumentException($e->getMessage());
    }
  }

  /**
   * Validates the "core" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidCoreValue(): \Closure {
    return static function ($value): bool {
      if ($value === NULL) {
        return TRUE;
      }
      if (DrupalCoreVersionEnum::isValidKey($value)) {
        return TRUE;
      }
      return ComposerFacade::isValidVersionConstraint($value);
    };
  }

  /**
   * Validates the "profile" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidProfileValue(): \Closure {
    return static function ($value): bool {
      if ($value === NULL) {
        return TRUE;
      }
      $pattern = '/^[a-z][a-z_0-9]{2,}$/';
      return preg_match($pattern, $value);
    };
  }

  /**
   * Validates the "project-template" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidProjectTemplateValue(): \Closure {
    return static function ($value): bool {
      if ($value === NULL) {
        return TRUE;
      }
      return ComposerFacade::isValidPackageName($value);
    };
  }

  /**
   * Validates the "sut" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidSutValue(): \Closure {
    return function ($value): bool {
      if ($value === NULL) {
        return TRUE;
      }
      return $this->packageManager->exists($value);
    };
  }

  /**
   * Validates the resolved options.
   *
   * @throws \Acquia\Orca\Exception\OrcaInvalidArgumentException
   */
  private function validate(): void {
    if ($this->isBare() && $this->hasSut()) {
      throw new OrcaInvalidArgumentException('Cannot create a bare fixture with a SUT.');
    }
    if ($this->isBare() && $this->symlinkAll()) {
      throw new OrcaInvalidArgumentException('Cannot symlink all in a bare fixture.');
    }
    if ($this->isSutOnly() && !$this->hasSut()) {
      throw new OrcaInvalidArgumentException('Cannot create a SUT-only fixture without a SUT.');
    }
  }

  /**
   * Determines whether or not to force fixture creation.
   *
   * @return bool
   *   TRUE to force it or FALSE not to.
   */
  public function force(): bool {
    return $this->options['force'];
  }

  /**
   * Gets the Drupal core version.
   *
   * @return string
   *   The Drupal core version.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  public function getCore(): string {
    $value = $this->options['core'];
    if (!$value && $this->isDev()) {
      return $this->findCoreVersion(DrupalCoreVersionEnum::CURRENT_DEV);
    }
    if (!$value) {
      return $this->findCoreVersion(DrupalCoreVersionEnum::CURRENT);
    }
    if (DrupalCoreVersionEnum::isValidKey($value)) {
      return $this->findCoreVersion($value);
    }
    return $this->options['core'];
  }

  /**
   * Finds the exact Drupal core version string for a given constraint.
   *
   * @param string $constraint
   *   The version constraint.
   *
   * @return string
   *   The exact version string.
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  private function findCoreVersion(string $constraint): string {
    $version = $this->drupalCoreVersionResolver
      ->resolvePredefined(new DrupalCoreVersionEnum($constraint));
    // Cache the value for subsequent calls.
    $this->options['core'] = $version;
    return $version;
  }

  /**
   * Gets the profile machine name.
   *
   * @return string
   *   The machine name.
   */
  public function getProfile(): string {
    if (empty($this->options['profile'])) {
      return 'orca';
    }
    return $this->options['profile'];
  }

  /**
   * Gets the Composer project template package name.
   *
   * @return string
   *   The package name.
   */
  public function getProjectTemplate(): string {
    // Allow users to override default templates via option.
    if ($this->options['project-template']) {
      return $this->options['project-template'];
    }

    // Use minimal project for SUT-only (i.e. isolated) jobs, which should
    // have no other company packages.
    if ($this->isSutOnly()) {
      $this->options['project-template'] = 'acquia/drupal-minimal-project';
    }
    else {
      $this->options['project-template'] = 'acquia/drupal-recommended-project';
    }

    return $this->options['project-template'];
  }

  /**
   * Gets the exact, resolved version of Drupal core.
   *
   * @return string
   *   The best match for the current version of Drupal core, accounting for
   *   ranges.
   */
  public function getCoreResolved(): string {
    if ($this->coreResolved) {
      return $this->coreResolved;
    }
    $core = $this->getCore();
    try {
      // Get the version if it's exact as opposed to a range.
      $parser = new VersionParser();
      $version = $parser->normalize($core);
      // Catch dev versions being treated as exact instead of ranges.
      if (strpos($version, 'dev') !== FALSE) {
        throw new \UnexpectedValueException('Version is a dev version.');
      }
    }
    catch (\UnexpectedValueException $e) {
      // The core version is a range. Get the best match.
      $stability = $this->isDev() ? 'dev' : 'stable';
      $version = $this->drupalCoreVersionResolver
        ->resolveArbitrary($core, $stability);
    }
    $this->coreResolved = $version;
    return $version;
  }

  /**
   * Gets the raw options data.
   *
   * @return array
   *   The options.
   */
  public function getRawOptions(): array {
    return $this->rawOptions;
  }

  /**
   * Gets the SUT if set.
   *
   * @return \Acquia\Orca\Domain\Package\Package|null
   *   The SUT as a package object if set or NULL if not.
   */
  public function getSut(): ?Package {
    $sut_name = $this->options['sut'];
    if (!$sut_name) {
      return NULL;
    }
    return $this->packageManager->get($sut_name);
  }

  /**
   * Determines whether the fixture has a SUT.
   *
   * @return bool
   *   TRUE if it does or FALSE if not.
   */
  public function hasSut(): bool {
    return (bool) $this->getSut();
  }

  /**
   * Determines whether or not to ignore Composer patch failures.
   *
   * @return bool
   *   TRUE to ignore them or FALSE not to.
   */
  public function ignorePatchFailure(): bool {
    return $this->options['ignore-patch-failure'];
  }

  /**
   * Determines whether or not to install the site.
   *
   * @return bool
   *   TRUE to install it or FALSE not to.
   */
  public function installSite(): bool {
    return !$this->options['no-site-install'];
  }

  /**
   * Determines whether or not the fixture is bare.
   *
   * @return bool
   *   TRUE if it is or FALSE if not.
   */
  public function isBare(): bool {
    return $this->options['bare'];
  }

  /**
   * Determines whether or not the fixture is a dev fixture.
   *
   * @return bool
   *   TRUE if it is or FALSE if it isn't.
   */
  public function isDev(): bool {
    return $this->options['dev'];
  }

  /**
   * Determines whether the fixture is SUT-only.
   *
   * @return bool
   *   TRUE if it is or FALSE if not.
   */
  public function isSutOnly(): bool {
    return $this->options['sut-only'];
  }

  /**
   * Determines whether or not to prefer source.
   *
   * @return bool
   *   TRUE to prefer source or FALSE not to.
   */
  public function preferSource(): bool {
    return $this->options['prefer-source'];
  }

  /**
   * Determines whether or not to symlink all.
   *
   * @return bool
   *   TRUE to symlink all or FALSE not to.
   */
  public function symlinkAll(): bool {
    return $this->options['symlink-all'];
  }

  /**
   * Determines whether or not to use SQLite as the database backend.
   *
   * @return bool
   *   TRUE to use it or FALSE not to.
   */
  public function useSqlite(): bool {
    return !$this->options['no-sqlite'];
  }

}
