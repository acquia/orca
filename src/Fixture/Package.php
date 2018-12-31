<?php

namespace Acquia\Orca\Fixture;

/**
 * Provides access to a package's details.
 */
class Package {

  /**
   * The raw package data supplied to the constructor.
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
   * The path the package installs at relative to the fixture root.
   *
   * @var string
   */
  private $installPath;

  /**
   * The package name.
   *
   * E.g., "drupal/example".
   *
   * @var string
   */
  private $packageName;

  /**
   * The project name.
   *
   * E.g., "example".
   *
   * @var string
   */
  private $projectName;

  /**
   * The URL for the Composer path repository.
   *
   * E.g., "../example" or "/var/www/example/modules/submodule".
   *
   * @var string
   */
  private $repositoryUrl;

  /**
   * The type.
   *
   * E.g., "drupal-module".
   *
   * @var string
   */
  private $type = 'drupal-module';

  /**
   * The version constraint.
   *
   * E.g., "*" or "~1.0".
   *
   * @var string
   */
  private $version = '*';

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
   *   - "version": (optional) The package version to require via Composer.
   *     Defaults to "*".
   */
  public function __construct(Fixture $fixture, array $data) {
    $this->fixture = $fixture;
    $this->data = $data;
    $this->initializePackageName();
    $this->initializeProjectName();
    $this->initializeRepositoryUrl();
    $this->initializeInstallPath();
    $this->initializeType();
    $this->initializeVersion();
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
   */
  public function getInstallPathRelative(): string {
    if (!empty($this->installPath)) {
      return $this->installPath;
    }

    switch ($this->getType()) {
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
   * Sets the path the package installs at relative to the fixture root.
   *
   * @param string $install_path
   *   The install path relative to the fixture root.
   *
   * @return self
   */
  public function setInstallPathRelative(string $install_path): Package {
    $this->installPath = $install_path;
    return $this;
  }

  /**
   * Gets the URL for the Composer path repository.
   *
   * E.g., "../example" or "/var/www/example/modules/submodule".
   */
  public function getRepositoryUrl(): string {
    return $this->repositoryUrl;
  }

  /**
   * Sets the URL for the Composer path repository.
   *
   * @param string $url
   *   An absolute path or a path relative to the fixture root, e.g.,
   *   "../example" or "/var/www/example/modules/submodule".
   *
   * @return self
   */
  public function setRepositoryUrl(string $url): Package {
    $this->repositoryUrl = $url;
    return $this;
  }

  /**
   * Gets the type.
   *
   * E.g., "drupal-module".
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Sets the type.
   *
   * @param string $type
   *   The type, e.g., "drupal-module".
   *
   * @return self
   */
  public function setType(string $type): Package {
    $this->type = $type;
    return $this;
  }

  /**
   * Gets the package name.
   *
   * E.g., "drupal/example".
   *
   * @return string
   */
  public function getPackageName(): string {
    return $this->packageName;
  }

  /**
   * Sets the package name.
   *
   * @param string $name
   *   The name, e.g., "drupal/example".
   *
   * @return self
   */
  public function setPackageName(string $name): Package {
    $this->packageName = $name;
    return $this;
  }

  /**
   * Gets the package string.
   *
   * Gets the package string as passed to `composer require`, e.g.,
   * "drupal/example:~1.0".
   *
   * @return string
   */
  public function getPackageString(): string {
    return "{$this->getPackageName()}:{$this->getVersion()}";
  }

  /**
   * Gets the project name.
   *
   * E.g., "example".
   *
   * @return string
   */
  public function getProjectName(): string {
    return $this->projectName;
  }

  /**
   * Sets the project name.
   *
   * @param string $name
   *   The project name, e.g., "example".
   *
   * @return self
   */
  public function setProjectName(string $name): Package {
    $this->projectName = $name;
    return $this;
  }

  /**
   * Gets the version constraint.
   *
   * E.g., "*" or "~1.0".
   *
   * @return string
   */
  public function getVersion(): string {
    return $this->version;
  }

  /**
   * Sets the version constraint.
   *
   * @param string $version
   *   The version constraint, e.g., "*" or "~1.0".
   *
   * @return self
   */
  public function setVersion(string $version): Package {
    $this->version = $version;
    return $this;
  }

  /**
   * Initializes the package name.
   */
  private function initializePackageName(): void {
    if (!array_key_exists('name', $this->data)) {
      throw new \InvalidArgumentException('Missing required property: "name"');
    }
    elseif (empty($this->data['name']) || !is_string($this->data['name']) || strpos($this->data['name'], '/') === FALSE) {
      throw new \InvalidArgumentException(sprintf('Invalid value for "name" property: %s', var_export($this->data['name'], TRUE)));
    }

    $this->setPackageName($this->data['name']);
  }

  /**
   * Initializes the project name.
   */
  private function initializeProjectName(): void {
    $name_parts = explode('/', $this->getPackageName());
    $name = $name_parts[count($name_parts) - 1];
    $this->setProjectName($name);
  }

  /**
   * Initializes the repository URL.
   *
   * I.e., the URL (path) of the package relative to the ORCA project directory
   * as determined by its Git repository name.
   */
  private function initializeRepositoryUrl(): void {
    $this->setRepositoryUrl("../{$this->getProjectName()}");

    if (!empty($this->data['url'])) {
      $this->setRepositoryUrl($this->data['url']);
    }
  }

  /**
   * Initializes the install path.
   */
  private function initializeInstallPath(): void {
    if (!empty($this->data['install_path'])) {
      $this->setInstallPathRelative($this->data['install_path']);
    }
  }

  /**
   * Initializes the type.
   */
  private function initializeType(): void {
    if (!empty($this->data['type'])) {
      $this->setType($this->data['type']);
    }
  }

  /**
   * Initializes the version.
   */
  private function initializeVersion(): void {
    if (!empty($this->data['version'])) {
      $this->setVersion($this->data['version']);
    }
  }

}
