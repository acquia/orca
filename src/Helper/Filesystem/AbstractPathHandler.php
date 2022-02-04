<?php

namespace Acquia\Orca\Helper\Filesystem;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides path-handling facilities.
 */
abstract class AbstractPathHandler {

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The base path.
   *
   * @var string
   */
  private $basePath;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param string $base_path
   *   The base path.
   */
  public function __construct(Filesystem $filesystem, string $base_path) {
    $this->filesystem = $filesystem;
    $this->basePath = $base_path;
  }

  /**
   * Determines whether or not the path exists.
   *
   * @param string|null $sub_path
   *   A sub-path to append.
   *
   * @return bool
   *   TRUE if the path exists on the filesystem or FALSE if not.
   */
  public function exists(?string $sub_path = NULL): bool {
    return $this->filesystem
      ->exists($this->getPath($sub_path));
  }

  /**
   * Gets the base path with an optional sub-path appended.
   *
   * @param string|null $sub_path
   *   A sub-path to append.
   *
   * @return string
   *   The base path with sub-path appended if provided.
   */
  public function getPath(?string $sub_path = NULL): string {
    if (!$sub_path) {
      return $this->normalizePath($this->basePath);
    }

    if (strpos($sub_path, '/') === 0) {
      $path = $sub_path;
    }
    else {
      $path = "{$this->basePath}/{$sub_path}";
    }
    return $this->normalizePath($path);
  }

  /**
   * Approximate realpath() without requiring the path parts to exist yet.
   *
   * @param string $path
   *   The path.
   *
   * @return string
   *   The normalized path.
   *
   * @see https://stackoverflow.com/a/14354948/895083
   */
  private function normalizePath(string $path): string {
    $patterns = ['~/{2,}~', '~/(\./)+~', '~([^/\.]+/(?R)*\.{2,}/)~', '~\.\./~'];
    $replacements = ['/', '/', '', ''];
    $path = preg_replace($patterns, $replacements, $path);

    // Remove trailing slashes.
    return rtrim($path, '/');
  }

}
