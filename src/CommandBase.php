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
   * The base fixture Git branch.
   */
  const BASE_FIXTURE_BRANCH = 'base-fixture';

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
    return $this->taskBlt('drupal:install -n');
  }

  /**
   * Returns a BLT task.
   *
   * @param string $command
   *   The command string to execute, including options and arguments.
   *
   * @return \Robo\Task\Base\Exec
   */
<<<<<<< Updated upstream
  protected function taskBlt($command) {
    return $this->taskExec(self::BUILD_DIR . "/vendor/bin/blt {$command}");
=======
  protected function taskBltExec($command) {
    return $this->taskExec(self::BUILD_DIR . "/vendor/bin/blt {$command}")
      ->dir(self::BUILD_DIR);
>>>>>>> Stashed changes
  }

  /**
   * Gets the list of Acquia product module Composer package strings.
   *
   * @return string[]
   *   An indexed array of package strings, including constraints, e.g.,
   *   "drupal/example:^1.0".
   */
  protected function getAcquiaProductModulePackageStrings() {
    return require __DIR__ . '/../config/product-modules.inc.php';
  }

}
