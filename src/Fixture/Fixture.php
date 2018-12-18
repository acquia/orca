<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\Utility\ConfigLoader;
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
 */
class Fixture {

  public const ACQUIA_MODULE_PATH = 'docroot/modules/contrib/acquia';

  public const BASE_FIXTURE_GIT_BRANCH = 'base-fixture';

  public const WEB_ADDRESS = '127.0.0.1:8080';

  /**
   * The config loader.
   *
   * @var \Acquia\Orca\Utility\ConfigLoader
   */
  private $configLoader;

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The project manager.
   *
   * @var \Acquia\Orca\Fixture\ProjectManager
   */
  private $projectManager;

  /**
   * The root path.
   *
   * @var string
   */
  private $rootPath;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Utility\ConfigLoader $configLoader
   *   The config loader.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param string $fixture_dir
   *   The absolute path of the fixture root directory.
   * @param \Acquia\Orca\Fixture\ProjectManager $project_manager
   *   The project manager.
   */
  public function __construct(ConfigLoader $configLoader, Filesystem $filesystem, string $fixture_dir, ProjectManager $project_manager) {
    $this->configLoader = $configLoader;
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
    return $this->filesystem->exists($this->getPath());
  }

  /**
   * Gets the fixture root path with an optional sub-path appended.
   *
   * @param string|null $sub_path
   *   (Optional) A sub-path to append.
   *
   * @return string
   */
  public function getPath(?string $sub_path = ''): string {
    $path = $this->rootPath;

    // Append optional subpath.
    if ($sub_path) {
      $path .= "/{$sub_path}";
    }

    // Approximate realpath() without requiring the path parts to exist yet.
    // @see https://stackoverflow.com/a/14354948/895083
    $patterns = ['~/{2,}~', '~/(\./)+~', '~([^/\.]+/(?R)*\.{2,}/)~', '~\.\./~'];
    $replacements = ['/', '/', '', ''];
    $path = preg_replace($patterns, $replacements, $path);

    // Remove trailing slashes.
    $path = rtrim($path, '/');

    return $path;
  }

  /**
   * Gets the directory to find tests under.
   *
   * An integrated test (standard fixture) runs tests found in all Acquia
   * modules. An isolated test (SUT-only fixture) runs only those found in the
   * SUT.
   *
   * @return string
   */
  public function getTestsPath(): string {
    $path = $this->getPath(self::ACQUIA_MODULE_PATH);

    $config = $this->configLoader
      ->load($this->getPath('composer.json'));
    $sut_name = $config->get('extra.orca.sut');
    $is_sut_only = $config->get('extra.orca.sut-only');

    if (!$sut_name || !$is_sut_only) {
      return $path;
    }

    $sut = $this->projectManager->get($sut_name);
    return $this->getPath($sut->getInstallPathRelative());
  }

}
