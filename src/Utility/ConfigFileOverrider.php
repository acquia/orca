<?php

namespace Acquia\Orca\Utility;

use Symfony\Component\Filesystem\Filesystem;

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
   */
  public function setPaths(string $source, string $dest): void {
    $this->sourcePath = realpath($source);
    $this->destPath = $this->normalizePath($dest);
  }

  public function normalizePath(string $dest): string {
    $path = realpath(dirname($dest));
    $filename = basename($dest);
    return "{$path}/{$filename}";
  }

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
   */
  public function destFileExists(): bool {
    return $this->filesystem->exists($this->destPath);
  }

  /**
   * Backs up the contents of a preexisting destination file.
   */
  private function backupDestFile() {
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
    $this->filesystem->remove($this->destPath);
    if ($this->destBackup) {
      file_put_contents($this->destPath, $this->destBackup);
    }
  }

}
