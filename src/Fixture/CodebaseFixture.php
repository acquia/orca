<?php

namespace Acquia\Orca\Fixture;

/**
 * Provides a codebase fixture.
 */
class CodebaseFixture implements FixtureInterface {

  /**
   * The ORCA root directory.
   *
   * @var string
   */
  private $orcaRoot;

  /**
   * Constructs an instance.
   *
   * @param string $orca_root
   *   The absolute path of the ORCA root directory.
   */
  public function __construct(string $orca_root) {
    $this->orcaRoot = $orca_root;
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
    $path = "{$this->orcaRoot}-build";
    if ($sub_path) {
      $path .= "/{$sub_path}";
    }
    return $path;
  }

  /**
   * Gets the codebase docroot path with an optional sub-path appended.
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

}
