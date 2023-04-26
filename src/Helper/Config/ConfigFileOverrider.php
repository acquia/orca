<?php

namespace Acquia\Orca\Helper\Config;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Temporarily overrides configuration files.
 */
class ConfigFileOverrider {

  /**
   * The original contents of the destination file, if preexisting.
   *
   * @var string
   */
  private $destBackup = '';

  /**
   * The full path to the destination file.
   *
   * @var string
   */
  private $destPath = '';

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The full path to the source file.
   *
   * @var string
   */
  private $sourcePath = '';

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   */
  public function __construct(Filesystem $filesystem) {
    $this->filesystem = $filesystem;
  }

  /**
   * Sets the paths.
   *
   * @param string $source
   *   The path to the source file.
   * @param string $dest
   *   The path to the destination file.
   */
  public function setPaths(string $source, string $dest): void {
    $this->sourcePath = realpath($source);
    $this->destPath = $this->normalizePath($dest);
  }

  /**
   * Normalizes a given path.
   *
   * Resolves relative path components.
   *
   * @param string $dest
   *   A filesystem path.
   *
   * @return string
   *   The absolute normalized path.
   */
  public function normalizePath(string $dest): string {
    $path = realpath(dirname($dest));
    $filename = basename($dest);
    return "{$path}/{$filename}";
  }

  /**
   * Overrides the active configuration file.
   *
   * Backs up the preexisting file if there is one.
   */
  public function override(): void {
    if ($this->destFileExists()) {
      $this->backupDestFile();
    }
    $this->copyConfigFile();
  }

  /**
   * Determines whether or not the destination file already exists.
   *
   * @return bool
   *   TRUE if the destination file already exists or FALSE if not.
   */
  public function destFileExists(): bool {
    return $this->filesystem->exists($this->destPath);
  }

  /**
   * Backs up the contents of a preexisting destination file.
   */
  private function backupDestFile(): void {
    $this->destBackup = file_get_contents($this->destPath);
  }

  /**
   * Copies the configuration file into place.
   */
  public function copyConfigFile(): void {
    $this->filesystem->copy($this->sourcePath, $this->destPath, TRUE);
  }

  /**
   * Restores the original configuration.
   */
  public function restore(): void {
    //$this->filesystem->remove($this->destPath);
    if ($this->destBackup) {
      file_put_contents($this->destPath, $this->destBackup);
    }
  }

}
