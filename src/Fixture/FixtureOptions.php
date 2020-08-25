<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Drupal\DrupalCoreVersion;
use Acquia\Orca\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Helper\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Package\Package;
use Acquia\Orca\Package\PackageManager;
use Closure;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Encapsulates fixture options.
 */
class FixtureOptions {

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Composer\Composer
   */
  private $composer;

  /**
   * The Drupal Core version finder.
   *
   * @var \Acquia\Orca\Drupal\DrupalCoreVersionFinder
   */
  private $drupalCoreVersionFinder;

  /**
   * The resolved options.
   *
   * @var array
   */
  private $options;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Package\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Composer\Composer $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Drupal\DrupalCoreVersionFinder $drupal_core_version_finder
   *   The Drupal core version finder.
   * @param \Acquia\Orca\Package\PackageManager $package_manager
   *   The package manager.
   * @param array $options
   *   The options.
   *
   * @throws \Acquia\Orca\Helper\Exception\OrcaInvalidArgumentException
   */
  public function __construct(Composer $composer, DrupalCoreVersionFinder $drupal_core_version_finder, PackageManager $package_manager, array $options) {
    $this->composer = $composer;
    $this->drupalCoreVersionFinder = $drupal_core_version_finder;
    $this->packageManager = $package_manager;
    $this->resolve($options);
    $this->validate();
  }

  /**
   * Resolves the given options.
   *
   * @param array $options
   *   The options to resolve.
   *
   * @throws \Acquia\Orca\Helper\Exception\OrcaInvalidArgumentException
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
  private function isValidCoreValue(): Closure {
    return function ($value): bool {
      if ($value === NULL) {
        return TRUE;
      }
      return $this->composer->isValidVersionConstraint($value);
    };
  }

  /**
   * Validates the "profile" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidProfileValue(): Closure {
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
  private function isValidProjectTemplateValue(): Closure {
    return static function ($value): bool {
      if ($value === NULL) {
        return TRUE;
      }
      return Composer::isValidPackageName($value);
    };
  }

  /**
   * Validates the "sut" value.
   *
   * @return \Closure
   *   The validation function.
   */
  private function isValidSutValue(): Closure {
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
   * @throws \Acquia\Orca\Helper\Exception\OrcaInvalidArgumentException
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
   */
  public function getCore(): string {
    $value = $this->options['core'];
    if (!$value && $this->isDev()) {
      return $this->findCoreVersion(DrupalCoreVersion::CURRENT_DEV);
    }
    if (!$value) {
      return $this->findCoreVersion(DrupalCoreVersion::CURRENT_RECOMMENDED);
    }
    if (DrupalCoreVersion::isValidKey($value)) {
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
   */
  private function findCoreVersion(string $constraint): string {
    $version = $this->drupalCoreVersionFinder
      ->get(new DrupalCoreVersion($constraint));
    // Cache the value for subsequent calls.
    $this->options['core'] = $version;
    return $version;
  }

  /**
   * Gets the profile machine name.
   *
   * @return string|null
   *   The machine name.
   */
  public function getProfile(): ?string {
    return $this->options['profile'];
  }

  /**
   * Gets the Composer project template package name.
   *
   * @return string|null
   *   The package name.
   */
  public function getProjectTemplate(): ?string {
    return $this->options['project-template'];
  }

  /**
   * Gets the SUT if set.
   *
   * @return \Acquia\Orca\Package\Package|null
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
