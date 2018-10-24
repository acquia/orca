<?php

namespace Acquia\Orca\Fixture;

use Acquia\Orca\IoTrait;
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
 * @property string $rootPath
 */
class Facade {

  use IoTrait;

  const BASE_FIXTURE_GIT_BRANCH = 'base-fixture';

  const PRODUCT_MODULE_INSTALL_PATH = 'docroot/modules/contrib/acquia';

  /**
   * Constructs an instance.
   */
  public function __construct(Filesystem $filesystem, string $root_path = ORCA_FIXTURE_ROOT) {
    $this->filesystem = $filesystem;
    $this->rootPath = $root_path;
  }

  /**
   * Gets the fixture root path with an optional sub-path appended.
   *
   * @param string $sub_path
   *   (Optional) A sub-path to append.
   *
   * @return string
   */
  public function docrootPath(string $sub_path = ''): string {
    return $this->appendSubPath($this->rootPath('docroot'), $sub_path);
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
   * Gets the fixture product module install path.
   *
   * @return string
   */
  public function productModuleInstallPath(): string {
    return $this->rootPath(self::PRODUCT_MODULE_INSTALL_PATH);
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
    return $this->appendSubPath($this->rootPath, $sub_path);
  }

  /**
   * Appends an optional sub-path to a given path.
   *
   * @param string $base_path
   *   The base path to append the sub-path to.
   * @param string $sub_path
   *   (Optional) The sub-path to append. If omitted, the base path will be
   *   returned.
   *
   * @return string
   */
  private function appendSubPath(string $base_path, string $sub_path = '') {
    $path = $base_path;
    if ($sub_path) {
      $path .= "/{$sub_path}";
    }
    return $path;
  }

}
