<?php

namespace Acquia\Orca\Fixture;

use Composer\Json\JsonFile;
use Seld\JsonLint\ParsingException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Provide access to Acquia product submodules physically in the fixture.
 */
class SubmoduleManager {

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The finder.
   *
   * @var \Symfony\Component\Finder\Finder
   */
  private $finder;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The top-level Acquia projects.
   *
   * @var \Acquia\Orca\Fixture\Project[]
   */
  private $topLevelProjects;

  /**
   * The submodules found in the fixture.
   *
   * @var \Acquia\Orca\Fixture\Project[]
   */
  private $submodules = [];

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Symfony\Component\Finder\Finder $finder
   *   The finder.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\ProjectManager $project_manager
   *   The project manager.
   */
  public function __construct(Filesystem $filesystem, Finder $finder, Fixture $fixture, ProjectManager $project_manager) {
    $this->filesystem = $filesystem;
    $this->finder = $finder;
    $this->fixture = $fixture;
    $this->topLevelProjects = $project_manager->getMultiple();
  }

  /**
   * Gets an array of all Acquia submodules.
   *
   * @return \Acquia\Orca\Fixture\Project[]
   */
  public function getAll(): array {
    if ($this->submodules) {
      return $this->submodules;
    }
    $paths = $this->getAllProjectInstallPaths();
    $this->submodules = $this->getInPaths($paths);
    return $this->submodules;
  }

  /**
   * Gets an array of submodules of a given parent.
   *
   * @param \Acquia\Orca\Fixture\Project $project
   *   The project to search for submodules.
   *
   * @return \Acquia\Orca\Fixture\Project[]
   */
  public function getByParent(Project $project): array {
    $paths = [$this->fixture->rootPath($project->getInstallPathRelative())];
    return $this->getInPaths($paths);
  }

  /**
   * Gets an array of submodules in a given set of paths.
   *
   * @param string[] $paths
   *   The paths to search for submodules.
   *
   * @return \Acquia\Orca\Fixture\Project[]
   */
  public function getInPaths(array $paths): array {
    $submodules = [];
    foreach ($this->findSubmoduleComposerJsonFiles($paths) as $file) {
      $json_file = new JsonFile($file);
      $data = $json_file->read();
      $install_path = str_replace("{$this->fixture->rootPath()}/", '', $file->getPath());
      $project_data = [
        'name' => $data['name'],
        'install_path' => $install_path,
        'url' => $file->getPath(),
        'version' => '@dev',
      ];
      $submodules[$data['name']] = new Project($project_data);
    }
    return $submodules;
  }

  /**
   * Gets an array of project install paths.
   *
   * @return array
   */
  private function getAllProjectInstallPaths(): array {
    $paths = [];
    foreach ($this->topLevelProjects as $project) {
      $path = $this->fixture
        ->rootPath($project->getInstallPathRelative());
      if ($this->filesystem->exists($path)) {
        $paths[] = $path;
      }
    }
    return $paths;
  }

  /**
   * Finds all Acquia product submodule composer.json files.
   *
   * @param string[] $paths
   *   An array of paths to recursively search for submodules.
   *
   * @return \Symfony\Component\Finder\Finder|array
   */
  private function findSubmoduleComposerJsonFiles(array $paths) {
    if (!$paths) {
      return [];
    }
    return $this->finder
      ->files()
      ->followLinks()
      ->in($paths)
      ->notPath('vendor')
      ->name('composer.json')
      ->filter(function (\SplFileInfo $file) {
        return $this->isSubmoduleComposerJson($file);
      });
  }

  /**
   * Determines whether a given composer.json file belongs to a submodule.
   *
   * @param \SplFileInfo $file
   *   The file to examine.
   *
   * @return bool
   */
  private function isSubmoduleComposerJson(\SplFileInfo $file): bool {
    try {
      $data = (new JsonFile($file))->read();
    }
    // Ignore invalid composer.json files.
    catch (ParsingException $e) {
      return FALSE;
    }

    list($vendor_name, $package_name) = explode('/', $data['name']);

    // Ignore top level projects.
    if (in_array($data['name'], array_keys($this->topLevelProjects))) {
      return FALSE;
    }

    // Ignore everything but Drupal modules.
    if (empty($data['type']) || $data['type'] !== 'drupal-module') {
      return FALSE;
    }

    // Ignore modules that explicitly opt out of installation.
    if (isset($data['extra']['orca']['install']) && $data['extra']['orca']['install'] === FALSE) {
      return FALSE;
    }

    // Ignore modules that aren't under the "drupal" vendor name.
    if ($vendor_name !== 'drupal') {
      return FALSE;
    }

    // Ignore modules without a corresponding .info.yml file.
    $info_yml_file = "{$file->getPath()}/{$package_name}.info.yml";
    if (!$this->filesystem->exists($info_yml_file)) {
      return FALSE;
    }

    return TRUE;
  }

}
