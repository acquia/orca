<?php

namespace Acquia\Orca\Helper\Config;

use Acquia\Orca\Exception\FileNotFoundException as OrcaFileNotFoundException;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Exception\ParseError as OrcaParseError;
use Exception;
use Noodlehaus\Config;
use Noodlehaus\Exception\FileNotFoundException as NoodlehausFileNotFoundException;
use Noodlehaus\Exception\ParseException as NoodlehausParseException;

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
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   * @throws \Acquia\Orca\Exception\ParseError
   */
  public function load($paths): Config {
    try {
      return new Config($paths);
    }
    // @codeCoverageIgnoreStart
    catch (NoodlehausFileNotFoundException $e) {
      throw new OrcaFileNotFoundException($e->getMessage());
    }
    catch (NoodlehausParseException $e) {
      throw new OrcaParseError($e->getMessage());
    }
    catch (Exception $e) {
      throw new OrcaException($e->getMessage());
    }
    // @codeCoverageIgnoreEnd
  }

}
