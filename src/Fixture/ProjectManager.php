<?php

namespace Acquia\Orca\Fixture;

use Symfony\Component\Yaml\Parser;

/**
 * Provides access to projects specified in config.
 */
class ProjectManager {

  /**
   * All defined projects keyed by package name.
   *
   * @var \Acquia\Orca\Fixture\Project[]
   */
  private $projects = [];

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Yaml\Parser $parser
   *   The YAML parser.
   * @param string $projects_config
   *   The path to the projects configuration file relative to the ORCA project
   *   directory.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(Parser $parser, string $projects_config, string $project_dir) {
    $data = $parser->parseFile("{$project_dir}/{$projects_config}");
    foreach ($data as $datum) {
      $project = new Project($datum);
      $this->projects[$project->getPackageName()] = $project;
    }
  }

  /**
   * Determines whether a given project exists.
   *
   * @param string $package_name
   *   The package name of the project in question, e.g., "drupal/example".
   *
   * @return bool
   */
  public function exists(string $package_name): bool {
    return array_key_exists($package_name, $this->projects);
  }

  /**
   * Gets a project by package name.
   *
   * @param string $package_name
   *   The package name.
   *
   * @return \Acquia\Orca\Fixture\Project
   */
  public function get(string $package_name): Project {
    if (empty($this->projects[$package_name])) {
      throw new \InvalidArgumentException(sprintf('No such package: %s', $package_name));
    }
    return $this->projects[$package_name];
  }

  /**
   * Gets an array of projects or project values, optionally filtered by type.
   *
   * @param string|null $type
   *   (Optional) A type to filter to, e.g., "drupal-module", or NULL to not
   *   filter by type. Defaults to NULL.
   * @param string|null $getter
   *   (Optional) A getter method to call on the objects to get the return array
   *   values, or NULL to return the full objects. Defaults to NULL.
   *
   * @return \Acquia\Orca\Fixture\Project[]|string[]
   *   An array of projects or project properties keyed by package name.
   */
  public function getMultiple(?string $type = NULL, ?string $getter = NULL): array {
    $projects = [];
    foreach ($this->projects as $package_name => $project) {
      if ($type && $project->getType() !== $type) {
        continue;
      }

      if ($getter && method_exists($project, $getter)) {
        $projects[$package_name] = $project->{$getter}();
        continue;
      }

      $projects[$package_name] = $project;
    }
    return $projects;
  }

}
