<?php

namespace Acquia\Orca\Task;

use Acquia\Orca\Enum\PhpcsStandard;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Configures PHPCS.
 */
class PhpcsConfigurator {

  private const VALUE_PLACEHOLDER = '{{ STANDARD }}';

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

  /**
   * The ORCA project directory.
   *
   * @var string
   */
  private $projectDir;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(Filesystem $filesystem, string $project_dir) {
    $this->filesystem = $filesystem;
    $this->projectDir = $project_dir;
  }

  /**
   * The temporary directory.
   *
   * @var string|null
   */
  private $tempDir;

  /**
   * Prepares the temporary config.
   *
   * @param \Acquia\Orca\Enum\PhpcsStandard $standard
   *   The PHPCS standard to use.
   */
  public function prepareTemporaryConfig(PhpcsStandard $standard): void {
    $this->filesystem->mkdir($this->getTempDir());
    $this->filesystem->touch($this->getTemporaryConfigFile());
    $template = file_get_contents($this->getConfigFileTemplate());
    $contents = str_replace(self::VALUE_PLACEHOLDER, $standard->getValue(), $template);
    $this->filesystem->dumpFile($this->getTemporaryConfigFile(), $contents);
  }

  /**
   * Cleans up the temporary config.
   */
  public function cleanupTemporaryConfig(): void {
    $this->filesystem->remove($this->getTempDir());
  }

  /**
   * Gets the temporary directory.
   *
   * @return string
   *   The temporary directory.
   */
  public function getTempDir(): string {
    if ($this->tempDir) {
      return $this->tempDir;
    }

    $path = sprintf('%s/var/cache/phpcs/%s', $this->projectDir, uniqid());
    $this->tempDir = $path;
    return $this->tempDir;
  }

  /**
   * Gets the path to the config file template.
   *
   * @return string
   *   The path to the config file template.
   */
  private function getConfigFileTemplate(): string {
    return "{$this->projectDir}/resources/phpcs.xml";
  }

  /**
   * Gets the path to the temporary config file.
   *
   * @return string
   *   The path to the temporary config file.
   */
  private function getTemporaryConfigFile(): string {
    return "{$this->getTempDir()}/phpcs.xml";
  }

}
