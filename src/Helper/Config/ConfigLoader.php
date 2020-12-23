<?php

namespace Acquia\Orca\Helper\Config;

use Acquia\Orca\Exception\OrcaDirectoryNotFoundException;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaParseError;
use Noodlehaus\Config;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundException;
use Noodlehaus\Exception\ParseException as NoodlehausParseException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Loads configuration files.
 *
 * The sole purpose of this class is to make \Noodlehaus\Config an injectable
 * dependency.
 */
class ConfigLoader {

  /**
   * The filesystem.
   *
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  private $filesystem;

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
   * Loads configuration.
   *
   * @param string $path
   *   The filename of the configuration file.
   *
   * @return \Noodlehaus\Config
   *   A config object.
   *
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  public function load(string $path): Config {
    $this->assertDirectoryExists($path);
    return $this->createConfig($path);
  }

  /**
   * Asserts that the config directory exists.
   *
   * @param string $path
   *   The config directory.
   *
   * @throws \Acquia\Orca\Exception\OrcaDirectoryNotFoundException
   */
  private function assertDirectoryExists(string $path): void {
    $dir_path = $this->getDirPath($path);
    if (!$this->filesystem->exists($dir_path)) {
      throw new OrcaDirectoryNotFoundException("SUT is absent from expected location: {$dir_path}");
    }
  }

  /**
   * Gets the directory path from the given config file path.
   *
   * @param string $path
   *   The config file path.
   *
   * @return string
   *   The parent directory path.
   */
  private function getDirPath(string $path): string {
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    array_pop($parts);
    return implode(DIRECTORY_SEPARATOR, $parts);
  }

  /**
   * Creates the config object.
   *
   * @param string $path
   *   The path.
   *
   * @return \Noodlehaus\Config
   *   The config object.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   * @throws \Acquia\Orca\Exception\OrcaFileNotFoundException
   * @throws \Acquia\Orca\Exception\OrcaParseError
   */
  protected function createConfig($path): Config {
    try {
      return $this->loadConfig($path);
    }
    catch (NoodlehausFileNotFoundException $e) {
      throw new OrcaFileNotFoundException($e->getMessage());
    }
    catch (NoodlehausParseException $e) {
      throw new OrcaParseError($e->getMessage());
    }
    catch (\Exception $e) {
      throw new OrcaException($e->getMessage());
    }
  }

  /**
   * Actually loads the config object from the system.
   *
   * This method is extracted exclusively for testability.
   *
   * @param string|array $path
   *   The path.
   *
   * @return \Noodlehaus\Config
   *   The config object.
   */
  protected function loadConfig($path): Config {
    return new Config($path);
  }

}
