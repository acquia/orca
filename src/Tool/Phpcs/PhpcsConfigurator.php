<?php

namespace Acquia\Orca\Tool\Phpcs;

use Acquia\Orca\Enum\PhpcsStandardEnum;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
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
   * The ORCA path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\OrcaPathHandler
   */
  private $orca;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   * @param \Acquia\Orca\Helper\Filesystem\OrcaPathHandler $orca_path_handler
   *   The ORCA path handler.
   */
  public function __construct(Filesystem $filesystem, OrcaPathHandler $orca_path_handler) {
    $this->filesystem = $filesystem;
    $this->orca = $orca_path_handler;
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
   * @param \Acquia\Orca\Enum\PhpcsStandardEnum $standard
   *   The PHPCS standard to use.
   */
  public function prepareTemporaryConfig(PhpcsStandardEnum $standard): void {
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

    $path = $this->orca->getPath('var/cache/phpcs/{uniqid()}');
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
    return $this->orca->getPath('resources/phpcs.xml');
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
