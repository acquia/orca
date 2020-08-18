<?php

namespace Acquia\Orca\Helper\Config;

use Noodlehaus\Config;

/**
 * Loads configuration.
 *
 * The sole purpose of this class is to make \Noodlehaus\Config an injectable
 * dependency.
 */
class ConfigLoader {

  /**
   * Loads configuration.
   *
   * @param string|array $values
   *   A filename or an array of filenames of configuration files.
   *
   * @return \Noodlehaus\Config
   *   A config object.
   *
   * @throws \Exception
   *   In case of loading or parsing errors.
   */
  public function load($values): Config {
    return new Config($values);
  }

}
