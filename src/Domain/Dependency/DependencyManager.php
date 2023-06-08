<?php

namespace Acquia\Orca\Domain\Dependency;

use Acquia\Orca\Domain\Dependency\Dependency;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Parser;

class DependencyManager{

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private Filesystem $filesystem;

  /**
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private FixturePathHandler $fixture;

  /**
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private OrcaPathHandler $orca;

  /**
   * @var \Symfony\Component\Yaml\Parser
   */
  private Parser $parser;

  /**
   * All defined dependencies keyed by dependency name.
   *
   * @var \Acquia\Orca\Domain\Dependency\Dependency[]
   */
  private $dependencies = [];

  public function __construct(Filesystem $filesystem, FixturePathHandler $fixture_path_handler, OrcaPathHandler $orca_path_handler, Parser $parser, string $dependencies_config, ?string $dependencies_config_alter) {
    $this->filesystem = $filesystem;
    $this->fixture = $fixture_path_handler;
    $this->orca = $orca_path_handler;
    $this->parser = $parser;
    $this->initializeDependencies($fixture_path_handler, $dependencies_config, $dependencies_config_alter);
  }

  private function initializeDependencies(FixturePathHandler $fixture_path_handler, string $dependencies_config, ?string $dependencies_config_alter): void {
    $data = $this->parseYamlFile($this->orca->getPath($dependencies_config));
    if ($dependencies_config_alter) {
      $alter_path = $this->orca->getPath($dependencies_config_alter);
      $this->alterData = $this->parseYamlFile($alter_path);
      $data = array_merge($data, $this->alterData);
    }
    foreach ($data as $dependency_name => $datum) {
      // Skipping a null datum provides for a dependency to be effectively removed
      // from the active specification at runtime by setting its value to NULL
      // in the dependencies' configuration alter file.
      if ($datum === NULL) {
        continue;
      }

      // Add dependencies which have defined an empty array.
      if ($datum === []) {
        $this->addPackage($datum, $fixture_path_handler, $dependency_name);
        continue;
      }

      // Process core_matrix.
      if (array_key_exists('core_matrix', $datum)) {
        $constraints = array_values($datum['core_matrix']);
        foreach ($constraints as $constraint) {
          if ($this->containsValidVersion($constraint)) {
            $this->addPackage($datum, $fixture_path_handler, $dependency_name);
            break;
          }
        }
        continue;
      }

      if ($this->containsValidVersion($datum)) {
        $this->addPackage($datum, $fixture_path_handler, $dependency_name);
      }

    }

    ksort($this->dependencies);
  }


  /**
   * Parses a given YAML file and returns the data.
   *
   * @param string $file
   *   The file to parse.
   *
   * @return array
   *   The parsed data.
   */
  private function parseYamlFile(string $file): array {
    if (!$this->filesystem->exists($file)) {
      throw new \LogicException("No such file: {$file}");
    }
    $data = $this->parser->parseFile($file);
    if (!is_array($data)) {
      throw new \LogicException("Incorrect schema in {$file}. See config/dependencies.yml.");
    }
    return $data;
  }

  /**
   * Checks if a dependency is null.
   */
  private function containsValidVersion($data) : bool {

    if (!is_array($data)) {
      return FALSE;
    }

    if (!array_key_exists('version', $data) && !array_key_exists('version_dev', $data)) {
      return TRUE;
    }

    return (array_key_exists('version', $data) && !is_null($data['version'])) || (array_key_exists('version_dev', $data) && !is_null($data['version_dev']));
  }

  /**
   * Adds a dependency to the list of dependencies.
   */
  private function addPackage(array $datum, FixturePathHandler $fixture_path_handler, string $dependency_name): void {
    $dependency = new Dependency($datum, $fixture_path_handler, $this->orca, $dependency_name);
    $this->dependencies[$dependency_name] = $dependency;
  }

  /**
   * Determines whether a given dependency exists.
   *
   * @param string $dependency_name
   *   The dependency name of the dependency in question, e.g., "drupal/example".
   *
   * @return bool
   *   TRUE if the given dependency exists or FALSE if not.
   */
  public function exists(string $dependency_name): bool {
    return array_key_exists($dependency_name, $this->dependencies);
  }

  /**
   * Gets a dependency by dependency name.
   *
   * @param string $dependency_name
   *
   * @return \Acquia\Orca\Domain\Dependency\Dependency
   *   The requested dependency.
   *
   */
  public function get(string $dependency_name): Dependency {
    if (empty($this->dependencies[$dependency_name])) {
      throw new \InvalidArgumentException(sprintf('No such dependency: %s', $dependency_name));
    }
    return $this->dependencies[$dependency_name];
  }

  /**
   * Gets an array of all dependencies.
   *
   * @return \Acquia\Orca\Domain\Dependency\Dependency[]
   *   An array of dependencies keyed by dependency name.
   */
  public function getAll(): array {
    return $this->dependencies;
  }

  /**
   * Gets the dependencies config alter data.
   *
   * @return array
   *   An array of data keyed by dependency name.
   */
  public function getAlterData(): array {
    return $this->alterData;
  }

}