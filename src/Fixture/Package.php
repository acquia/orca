<?php

namespace Acquia\Orca\Fixture;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides access to a package's details.
 */
class Package {

  /**
   * The package data.
   *
   * @var array
   */
  private $data;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param array $data
   *   An array of package data that may contain the following key-value pairs:
   *   - "name": (required) The package name, corresponding to the "name"
   *     property in its composer.json file, e.g., "drupal/example".
   *   - "type": (optional) The package type, corresponding to the "type"
   *     property in its composer.json file. Defaults to "drupal-module".
   *   - "install_path": (optional) The path the package gets installed at
   *     relative to the fixture root, e.g., docroot/modules/contrib/example.
   *     Used for Drupal submodules. Defaults by "type" to match the
   *     "installer-paths" patterns specified by BLT.
   *   - "url": (optional) The path, absolute or relative to the fixture root,
   *     of a local clone of the package. Used for the "url" property of the
   *     Composer path repository used to symlink the system under test (SUT)
   *     into place. Defaults to a directory adjacent to the fixture root named
   *     the Composer project name, e.g., "../example" for a "drupal/example"
   *     project.
   *   - "version": (optional) The recommended package version to require via
   *     Composer. Defaults to "*".
   *   - "version": (required) The dev package version to require via Composer.
   */
  public function __construct(Fixture $fixture, array $data) {
    $this->fixture = $fixture;
    $this->data = $this->resolveData($data);
  }

  /**
   * Resolves the given package data.
   *
   * @param array $data
   *   The given package data.
   *
   * @return array
   *   The resolved package data.
   */
  private function resolveData(array $data): array {
    $resolver = (new OptionsResolver())
      ->setDefined([
        'name',
        'type',
        'install_path',
        'url',
        'version',
        'version_dev',
        'enable',
      ])
      ->setRequired(['name', 'version_dev'])
      ->setDefaults([
        'type' => 'drupal-module',
        'version' => '*',
        'enable' => TRUE,
      ])
      ->setAllowedTypes('name', 'string')
      ->setAllowedValues('name', function ($value) {
        // Require a a full package name: "vendor/project". A simple test for a
        // forward slash will suffice.
        return strpos($value, '/') !== FALSE;
      })
      ->setAllowedTypes('type', 'string')
      ->setAllowedTypes('install_path', 'string')
      ->setAllowedTypes('url', 'string')
      ->setAllowedTypes('version', 'string')
      ->setAllowedTypes('version_dev', 'string')
      ->setAllowedTypes('enable', 'boolean');
    return $resolver->resolve($data);
  }

  /**
   * Gets the absolute path the package installs at.
   *
   * @return string
   */
  public function getInstallPathAbsolute(): string {
    return $this->fixture->getPath($this->getInstallPathRelative());
  }

  /**
   * Gets the path the package installs at relative to the fixture root.
   *
   * @return string
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function getInstallPathRelative(): string {
    if (!empty($this->data['install_path'])) {
      return $this->data['install_path'];
    }

    switch ($this->getType()) {
      case 'drupal-core':
        return 'docroot/core';

      case 'drupal-drush':
        return "drush/Commands/{$this->getProjectName()}";

      case 'drupal-library':
      case 'bower-asset':
      case 'npm-asset':
        return "docroot/libraries/{$this->getProjectName()}";

      case 'drupal-module':
        return "docroot/modules/contrib/{$this->getProjectName()}";

      case 'drupal-profile':
        return "docroot/profiles/contrib/{$this->getProjectName()}";

      case 'drupal-theme':
        return "docroot/themes/contrib/{$this->getProjectName()}";

      default:
        return "vendor/{$this->getPackageName()}";
    }
  }

  /**
   * Gets the URL for the Composer path repository.
   *
   * E.g., "../example" or "/var/www/example/modules/submodule".
   */
  public function getRepositoryUrl(): string {
    if (!empty($this->data['url'])) {
      return $this->data['url'];
    }

    return "../{$this->getProjectName()}";
  }

  /**
   * Gets the type.
   *
   * E.g., "drupal-module".
   */
  public function getType(): string {
    return $this->data['type'];
  }

  /**
   * Gets the package name.
   *
   * E.g., "drupal/example".
   *
   * @return string
   */
  public function getPackageName(): string {
    return $this->data['name'];
  }

  /**
   * Gets the dev version package string.
   *
   * Gets the package string as passed to `composer require`, e.g.,
   * "drupal/example:1.x-dev".
   *
   * @return string
   */
  public function getPackageStringDev(): string {
    return "{$this->getPackageName()}:{$this->getVersionDev()}";
  }

  /**
   * Gets the recommended version package string.
   *
   * Gets the package string as passed to `composer require`, e.g.,
   * "drupal/example:~1.0".
   *
   * @return string
   */
  public function getPackageStringRecommended(): string {
    return "{$this->getPackageName()}:{$this->getVersionRecommended()}";
  }

  /**
   * Gets the project name.
   *
   * E.g., "example".
   *
   * @return string
   */
  public function getProjectName(): string {
    $package_name_parts = explode('/', $this->data['name']);
    return $package_name_parts[count($package_name_parts) - 1];
  }

  /**
   * Gets the dev version constraint.
   *
   * E.g., "*" or "~1.0".
   *
   * @return string
   */
  public function getVersionDev(): string {
    return $this->data['version_dev'];
  }

  /**
   * Gets the recommended version constraint.
   *
   * E.g., "*" or "~1.0".
   *
   * @return string
   */
  public function getVersionRecommended(): string {
    return $this->data['version'];
  }

  /**
   * Determines whether the package is a Drupal module that should get enabled.
   *
   * @return bool
   */
  public function shouldGetEnabled(): bool {
    if ($this->getType() !== 'drupal-module') {
      return FALSE;
    }

    return $this->data['enable'];
  }

}
