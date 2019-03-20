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
   * The package name.
   *
   * @var string
   */
  private $packageName;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param string $package_name
   *   The package name, corresponding to the "name" property in its
   *   composer.json file, e.g., "drupal/example".
   * @param array $data
   *   An array of package data that may contain the following key-value pairs:
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
  public function __construct(Fixture $fixture, string $package_name, array $data) {
    $this->fixture = $fixture;
    $this->initializePackageName($package_name);
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
        'type',
        'install_path',
        'url',
        'version',
        'version_dev',
        'enable',
      ])
      ->setRequired(['version_dev'])
      ->setDefaults([
        'type' => 'drupal-module',
        'version' => '*',
        'enable' => TRUE,
      ])
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
   *   The absolute path the package installs at, e.g.,
   *   "/var/www/orca/docroot/modules/contrib/example".
   */
  public function getInstallPathAbsolute(): string {
    return $this->fixture->getPath($this->getInstallPathRelative());
  }

  /**
   * Gets the path the package installs at relative to the fixture root.
   *
   * @return string
   *   The path the package installs at relative to the fixture root, e.g.,
   *   "docroot/modules/contrib/example".
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
   * @return string
   *   The URL for the Composer path repository, e.g., "../example" or
   *   "/var/www/example/modules/submodule".
   */
  public function getRepositoryUrl(): string {
    if (!empty($this->data['url'])) {
      return $this->data['url'];
    }

    return "../{$this->getProjectName()}";
  }

  /**
   * Gets the package type.
   *
   * @return string
   *   The package type, e.g., "drupal-module".
   */
  public function getType(): string {
    return $this->data['type'];
  }

  /**
   * Gets the package name.
   *
   * @return string
   *   The package name, e.g., "drupal/example".
   */
  public function getPackageName(): string {
    return $this->packageName;
  }

  /**
   * Gets the dev version package string.
   *
   * @return string
   *   The package string as passed to `composer require`, e.g.,
   *   "drupal/example:1.x-dev".
   */
  public function getPackageStringDev(): string {
    return "{$this->getPackageName()}:{$this->getVersionDev()}";
  }

  /**
   * Gets the recommended version package string.
   *
   * @return string
   *   The package string as passed to `composer require`, e.g.,
   *   "drupal/example:~1.0".
   */
  public function getPackageStringRecommended(): string {
    return "{$this->getPackageName()}:{$this->getVersionRecommended()}";
  }

  /**
   * Gets the project name.
   *
   * @return string
   *   The project name, e.g., "example".
   */
  public function getProjectName(): string {
    $package_name_parts = explode('/', $this->packageName);
    return $package_name_parts[count($package_name_parts) - 1];
  }

  /**
   * Gets the dev version constraint.
   *
   * @return string
   *   The dev version constraint, e.g., "*" or "~1.0".
   */
  public function getVersionDev(): string {
    return $this->data['version_dev'];
  }

  /**
   * Gets the recommended version constraint.
   *
   * @return string
   *   The recommended version constraint, e.g., "*" or "~1.0".
   */
  public function getVersionRecommended(): string {
    return $this->data['version'];
  }

  /**
   * Determines whether the package is a Drupal module that should get enabled.
   *
   * @return bool
   *   TRUE if the package is a Drupal module that should get enabled or FALSE
   *   if not.
   */
  public function shouldGetEnabled(): bool {
    if ($this->getType() !== 'drupal-module') {
      return FALSE;
    }

    return $this->data['enable'];
  }

  /**
   * Initializes the package name.
   *
   * @param string $package_name
   *   The package name.
   *
   * @throws \InvalidArgumentException
   *   In case of an invalid package name.
   */
  private function initializePackageName(string $package_name): void {
    // Require a a full package name: "vendor/project". A simple test for a
    // forward slash will suffice.
    if (strpos($package_name, '/') === FALSE) {
      throw new \InvalidArgumentException("Invalid package name: {$package_name}. Must take the form 'vendor/project'.");
    }
    $this->packageName = $package_name;
  }

}
