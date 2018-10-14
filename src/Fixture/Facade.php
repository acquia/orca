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

  /**
   * The base fixture Git branch.
   */
  const BASE_BRANCH = 'base-fixture';

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param string $root_path
   *   The absolute path of the fixture root directory.
   */
  public function __construct(Filesystem $filesystem, string $root_path) {
    $this->filesystem = $filesystem;
    $this->rootPath = $root_path;
  }

  /**
   * Gets a fixture creator.
   *
   * @return \Acquia\Orca\Fixture\Creator
   */
  public function getCreator(): Creator {
    return new Creator($this, $this->filesystem, new ProductData());
  }

  /**
   * Gets a fixture destroyer.
   *
   * @return \Acquia\Orca\Fixture\Destroyer
   */
  public function getDestroyer(): Destroyer {
    return new Destroyer($this, $this->filesystem);
  }

  /**
   * Gets the codebase root path with an optional sub-path appended.
   *
   * @param string $sub_path
   *   (Optional) A sub-path to append.
   *
   * @return string
   */
  public function docrootPath(string $sub_path = ''): string {
    $path = $this->rootPath('docroot');
    if ($sub_path) {
      $path .= "/{$sub_path}";
    }
    return $path;
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
   * Gets the codebase root path with an optional sub-path appended.
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

}
