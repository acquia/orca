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
   */
  public function __construct(Filesystem $filesystem, string $base_path) {
    $this->filesystem = $filesystem;
    $this->basePath = $base_path;
  }

  /**
   * Determines whether or not the path exists.
   *
   * @return bool
   *   TRUE if the path exists on the filesystem or FALSE if not.
   */
  public function exists(?string $sub_path = ''): bool {
    return $this->filesystem
      ->exists($this->getPath($sub_path));
  }

  /**
   * Gets the base path with an optional sub-path appended.
   *
   * @param string|null $sub_path
   *   (Optional) A sub-path to append.
   *
   * @return string
   *   The base path with sub-path appended if provided.
   */
  public function getPath(?string $sub_path = ''): string {
    $path = $this->basePath;

    // Append optional sub-path.
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

}
