<?php

namespace Acquia\Orca\Helper\Config;

use Noodlehaus\Config;

/**
 * Loads configuration files.
 *
 * The sole purpose of this class is to make \Noodlehaus\Config an injectable
 * dependency.
 */
class ConfigLoader {

  /**
   * Loads configuration.
   *
   * @param string|string[] $paths
   *   A filename or an array of filenames of configuration files.
   *
   * @return \Noodlehaus\Config
   *   A config object.
   *
   * @throws \Noodlehaus\Exception\FileNotFoundException
   * @throws \Noodlehaus\Exception\ParseException
   */
  public function load($paths): Config {
    return new Config($paths);
  }

}
