<?php

namespace Acquia\Orca\Fixture;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides access to the test fixture.
 *
 * In automated testing, a test fixture is all the things we need to have in
 * place in order to run a test and expect a particular outcome.
 *
 * @see http://xunitpatterns.com/test%20fixture%20-%20xUnit.html
 *
 * In the case of ORCA, that means a BLT project with Acquia product modules in
 * place and Drupal installed.
 *
 * @property \Symfony\Component\Filesystem\Filesystem $filesystem
 * @property \Acquia\Orca\Fixture\ProjectManager $projectManager
 */
class Fixture {

  public const BASE_FIXTURE_GIT_BRANCH = 'base-fixture';

  public const WEB_ADDRESS = '127.0.0.1:8080';

  /**
   * The root path.
   *
   * @var string
   */
  private $rootPath = '';

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param string $fixture_dir
   *   The absolute path of the fixture root directory.
   * @param \Acquia\Orca\Fixture\ProjectManager $project_manager
   *   The project manager.
   */
  public function __construct(Filesystem $filesystem, string $fixture_dir, ProjectManager $project_manager) {
    $this->filesystem = $filesystem;
    $this->projectManager = $project_manager;
    $this->rootPath = $fixture_dir;
  }

  /**
   * Determines whether or not the fixture already exists.
   *
   * @return bool
   */
  public function exists(): bool {
    return $this->filesystem->exists($this->rootPath());
  }

  /**
   * Gets the fixture root path with an optional sub-path appended.
   *
   * @param string $sub_path
   *   (Optional) A sub-path to append.
   *
   * @return string
   */
  public function rootPath(string $sub_path = ''): string {
    $path = $this->rootPath;
    if ($sub_path) {
      $path .= "/{$sub_path}";
    }
    return $path;
  }

  /**
   * Gets the directory to find tests under.
   *
   * @return string
   */
  public function testsDirectory(): string {
    // Default to the product module install path so as to include all modules.
    $directory = $this->rootPath('docroot/modules/contrib/acquia');

    $composer_config = $this->loadComposerJson();
    if (!empty($composer_config['extra']['orca']['sut'])) {
      $sut = $this->projectManager->get($composer_config['extra']['orca']['sut']);
      // Only limit the tests run for a SUT-only fixture.
      if (!empty($composer_config['extra']['orca']['sut-only'])) {
        return $this->rootPath($sut->getInstallPathRelative());
      }
    }

    return $directory;
  }

  /**
   * Loads the fixture's composer.json data.
   */
  private function loadComposerJson(): array {
    $json = file_get_contents($this->rootPath('composer.json'));
    return json_decode($json, TRUE);
  }

}
