<?php

namespace Acquia\Orca\Domain\Package;

use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Composer\Semver\VersionParser;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides access to a package's details.
 */
class Package {

  /**
   * The package core matrix.
   *
   * @var array
   */
  private $coreMatrix;

  /**
   * The package data.
   *
   * @var array
   */
  private $data;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * The package name.
   *
   * @var string
   */
  private $packageName;

  /**
   * Constructs an instance.
   *
   * @param array $data
   *   For details, @see https://github.com/acquia/orca/config/packages.yml.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param string $package_name
   *   The package name, corresponding to the "name" property in its
   *   composer.json file, e.g., "drupal/example".
   */
  public function __construct(array $data, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, string $package_name) {
    $this->fixture = $fixture_path_handler;
    $this->initializePackageName($package_name);
    $this->orca = $orca_path_handler;
    $this->data = $this->resolveData($data);
    $this->coreMatrix = $this->resolveCoreMatrix($this->data['core_matrix']);
    unset($this->data['core_matrix']);
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
        'core_matrix',
        'enable',
        'is_company_package',
      ])
      ->setDefaults([
        'type' => 'drupal-module',
        'version' => '*',
        'version_dev' => '*@dev',
        'core_matrix' => [],
        'enable' => TRUE,
        'is_company_package' => TRUE,
      ])
      ->setAllowedTypes('type', 'string')
      ->setAllowedTypes('install_path', 'string')
      ->setAllowedTypes('url', 'string')
      ->setAllowedTypes('version', ['string', 'null'])
      ->setAllowedTypes('version_dev', ['string', 'null'])
      ->setAllowedTypes('core_matrix', 'array')
      ->setAllowedTypes('enable', 'boolean')
      ->setAllowedTypes('is_company_package', 'boolean');
    return $resolver->resolve($data);
  }

  /**
   * Resolves the given core matrix.
   *
   * @param array $matrix
   *   The given package core matrix.
   *
   * @return array
   *   The resolved package core matrix.
   */
  private function resolveCoreMatrix(array $matrix): array {
    $resolver = (new OptionsResolver())
      ->setDefined([
        'version',
        'version_dev',
      ])
      ->setAllowedTypes('version', ['string', 'null'])
      ->setAllowedTypes('version_dev', ['string', 'null']);
    $parser = new VersionParser();
    foreach ($matrix as $constraint => &$data) {
      $parser->parseConstraints($constraint);
      $data = $resolver->resolve($data);
    }
    return $matrix;
  }

  /**
   * Gets the Drupal extension machine name.
   *
   * Suitable for use with Drush, for example.
   *
   * @return string|null
   *   The Drupal extension machine name if available or NULL if not.
   */
  public function getDrupalExtensionName(): ?string {
    if (!$this->isDrupalExtension()) {
      return NULL;
    }

    // Project names may include a namespace.
    // @see https://www.drupal.org/project/project_composer/issues/3064900
    $name_parts = explode('-', $this->getProjectName());
    return end($name_parts);
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

      case 'project-template':
        return '.';

      default:
        return "vendor/{$this->getPackageName()}";
    }
  }

  /**
   * Gets the URL for the Composer "path" repository exactly as specified.
   *
   * @return string
   *   The URL for the Composer path repository, e.g., "../example" or
   *   "/var/www/example/modules/submodule".
   */
  public function getRepositoryUrlRaw(): string {
    if (!empty($this->data['url'])) {
      return $this->data['url'];
    }

    return "../{$this->getProjectName()}";
  }

  /**
   * Gets the absolute URL for the Composer "path" repository.
   *
   * @return string
   *   The absolute URL the Composer package is cloned at at, e.g.,
   *   "/var/www/example".
   */
  public function getRepositoryUrlAbsolute(): string {
    return $this->orca->getPath($this->getRepositoryUrlRaw());
  }

  /**
   * Determines whether or not the Composer "path" repository exists.
   *
   * @return bool
   *   Returns TRUE if the repository exists or FALSE if not.
   */
  public function repositoryExists(): bool {
    return $this->orca->exists($this->getRepositoryUrlRaw());
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
   * Gets the project name.
   *
   * @return string
   *   The project name, e.g., "example".
   */
  public function getProjectName(): string {
    $package_name_parts = explode('/', $this->packageName);
    return end($package_name_parts);
  }

  /**
   * Gets the dev version constraint.
   *
   * @param string|null $core_version
   *   The Drupal core version targeted if any or NULL if not.
   *
   * @return string|null
   *   The dev version constraint, e.g., "*@dev" or "1.x-dev", if available or
   *   NULL if not.
   */
  public function getVersionDev(string $core_version = NULL): ?string {
    return $this->getVersion('version_dev', $core_version);
  }

  /**
   * Gets the recommended version constraint.
   *
   * @param string|null $core_version
   *   The Drupal core version targeted if any or NULL if not.
   *
   * @return string|null
   *   The recommended version constraint, e.g., "*" or "~1.0", if available or
   *   NULL if not.
   */
  public function getVersionRecommended(string $core_version = NULL): ?string {
    return $this->getVersion('version', $core_version);
  }

  /**
   * Determines whether the package is a Drupal extension.
   *
   * @return bool
   *   Returns TRUE if it is, or FALSE if not.
   */
  public function isDrupalExtension(): bool {
    return $this->isDrupalModule() || $this->isDrupalTheme();
  }

  /**
   * Determines whether the package is a Drupal module.
   *
   * @return bool
   *   Returns TRUE if it is, or FALSE if not.
   */
  public function isDrupalModule(): bool {
    return $this->getType() === 'drupal-module';
  }

  /**
   * Determines whether the package is a Drupal theme.
   *
   * @return bool
   *   Returns TRUE if it is, or FALSE if not.
   */
  public function isDrupalTheme(): bool {
    return $this->getType() === 'drupal-theme';
  }

  /**
   * Determines whether the package is a project template.
   *
   * @return bool
   *   Returns TRUE if it is, or FALSE if not.
   */
  public function isProjectTemplate(): bool {
    return $this->getType() === 'project-template';
  }

  /**
   * Determines whether the package is a Drupal module that should get enabled.
   *
   * @return bool
   *   TRUE if the package is a Drupal extension that should get enabled or
   *   FALSE if not.
   */
  public function shouldGetEnabled(): bool {
    if (!$this->isDrupalExtension()) {
      return FALSE;
    }

    return $this->data['enable'];
  }

  /**
   * Determines whether the package is a company package.
   *
   * @return bool
   *   TRUE if the package is a company package or FALSE if not.
   */
  public function isCompanyPackage(): bool {
    return $this->data['is_company_package'];
  }

  /**
   * Determines whether the package should get required with Composer.
   *
   * @return bool
   *   TRUE if the package should get required with Composer or FALSE if not.
   */
  public function shouldGetComposerRequired(): bool {
    if ($this->isProjectTemplate()) {
      return FALSE;
    }

    return TRUE;
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
    // Require a full package name: "vendor/project". A simple test for a
    // forward slash will suffice.
    if (strpos($package_name, '/') === FALSE) {
      throw new \InvalidArgumentException("Invalid package name: {$package_name}. Must take the form 'vendor/project'.");
    }
    $this->packageName = $package_name;
  }

  /**
   * Gets the package version for a given version of Drupal core.
   *
   * @param string $which
   *   Which version to get, one of "version" or "version_dev".
   * @param string|null $core_version
   *   (Optional) The version of Drupal core to target, e.g., "8.7.0".
   *
   * @return string|null
   *   The package version if available or NULL if not.
   */
  private function getVersion(string $which, string $core_version = NULL): ?string {
    $match = $this->data[$which];

    if (!$core_version) {
      return $match;
    }

    foreach ($this->coreMatrix as $constraint => $data) {
      if (!array_key_exists($which, $data)) {
        continue;
      }

      $parser = new VersionParser();
      $required = $parser->parseConstraints($constraint);
      $provided = $parser->parseConstraints($core_version);

      if ($required->matches($provided)) {
        $match = $data[$which];
        break;
      }
    }

    return $match;
  }

}
