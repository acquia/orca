<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Robo\Tasks;

/**
 * Provides a base Robo command implementation.
 */
abstract class CommandBase extends Tasks {

  /**
   * The relative path to the build directory.
   */
  const BUILD_DIR = '../build';

  /**
   * Executes the command.
   *
   * @return \Robo\ResultData
   */
  abstract public function execute();

  /**
   * Installs Drupal.
   *
   * @return \Robo\Task\Base\Exec
   */
  protected function installDrupal() {
    return $this->taskExec(self::BUILD_DIR . '/vendor/bin/blt drupal:install -n');
  }

}
