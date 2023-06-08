<?php

namespace Acquia\Orca\Domain\Dependency;


use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Composer\Semver\VersionParser;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Dependency{

  private string $dependencyName;

  /**
   * Constructs an instance.
   *
   * @param array $data
   *   An array of dependency data that may contain the following key-value pairs:
   *   - "type": (optional) The dependency type, corresponding to the "type"
   *     property in its composer.json file. Defaults to "drupal-module".
   *   - "install_path": (optional) The path the dependency gets installed at
   *     relative to the fixture root, e.g., docroot/modules/contrib/example.
   *     Used for Drupal subextensions. Defaults by "type" to match the
   *     "installer-paths" patterns specified by the root Composer project.
   *   - "url": (optional) The path, absolute or relative to the fixture root,
   *     of a local clone of the dependency. Used for the "url" property of the
   *     Composer path repository used to symlink the system under test (SUT)
   *     into place. Defaults to a directory adjacent to the fixture root named
   *     the Composer project name, e.g., "../example" for a "drupal/example"
   *     project.
   *   - "version": (optional) The recommended dependency version to require via
   *     Composer. Defaults to "*".
   *   - "version_dev": (optional) The dev dependency version to require via
   *     Composer. Defaults to "*@dev".
   *   - "core_matrix": (optional) An array of dependency version mappings. Each
   *     mapping is keyed by a Drupal core version constraint, e.g., "8.7.x",
   *     with a value of an associative array optionally containing either or
   *     both of the "version" and "version_dev" key-value pairs to be used when
   *     the corresponding Drupal core version constraint is satisfied. Mappings
   *     are processed in order, and the first match wins.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   * @param string $dependency_name
   *   The dependency name, corresponding to the "name" property in its
   *   composer.json file, e.g., "drupal/example".
   *
   * @see \Acquia\Orca\Tests\Fixture\PackageTest::testConditionalVersions
   *   - "enable": (internal) TRUE if the dependency is a Drupal module that should
   *     be automatically enabled or FALSE if not. Defaults to TRUE for modules.
   *     Always FALSE for anything else.
   */
  public function __construct(array $data, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, string $dependency_name) {
    $this->fixture = $fixture_path_handler;
    $this->initializeDependencyName($dependency_name);
    $this->orca = $orca_path_handler;
    $this->data = $this->resolveData($data);
    $this->coreMatrix = $this->resolveCoreMatrix($this->data['core_matrix']);
    unset($this->data['core_matrix']);
  }

  /**
   * Initializes the dependency name.
   *
   * @param string $dependency_name
   *   The dependency name.
   *
   * @throws \InvalidArgumentException
   *   In case of an invalid dependency name.
   */
  private function initializeDependencyName(string $dependency_name): void {
    // Require a full dependency name: "vendor/project". A simple test for a
    // forward slash will suffice.
    if (!str_contains($dependency_name, '/')) {
      throw new \InvalidArgumentException("Invalid dependency name: {$dependency_name}. Must take the form 'vendor/project'.");
    }
    $this->dependencyName = $dependency_name;
  }

  /**
   * Resolves the given dependency data.
   *
   * @param array $data
   *   The given dependency data.
   *
   * @return array
   *   The resolved dependency data.
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
      ])
      ->setDefaults([
        'type' => 'drupal-module',
        'version' => '*',
        'version_dev' => '*@dev',
        'core_matrix' => [],
        'enable' => TRUE,
      ])
      ->setAllowedTypes('type', 'string')
      ->setAllowedTypes('install_path', 'string')
      ->setAllowedTypes('url', 'string')
      ->setAllowedTypes('version', ['string', 'null'])
      ->setAllowedTypes('version_dev', ['string', 'null'])
      ->setAllowedTypes('core_matrix', 'array')
      ->setAllowedTypes('enable', 'boolean');
    return $resolver->resolve($data);
  }

  /**
   * Resolves the given core matrix.
   *
   * @param array $matrix
   *   The given dependency core matrix.
   *
   * @return array
   *   The resolved dependency core matrix.
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
   * Gets the dependency version for a given version of Drupal core.
   *
   * @param string $which
   *   Which version to get, one of "version" or "version_dev".
   * @param string|null $core_version
   *   (Optional) The version of Drupal core to target, e.g., "8.7.0".
   *
   * @return string|null
   *   The dependency version if available or NULL if not.
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

  /**
   * Gets the dependency name.
   *
   * @return string
   *   The dependency name, e.g., "drupal/example".
   */
  public function getPackageName(): string {
    return $this->dependencyName;
  }

}