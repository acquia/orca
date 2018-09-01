<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Robo\Tasks;

/**
 * Provides a base Robo command implementation.
 */
abstract class CommandBase extends Tasks {

  /**
   * The build directory relative to the repo root of the module under test.
   */
  const BUILD_DIR = '../build';

  /**
   * @return \Robo\ResultData
   */
  abstract public function execute();

}
