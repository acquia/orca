<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Provides the "fixture:destroy" command.
 */
class FixtureDestroyCommand extends CommandBase {

  /**
   * Destroys the test fixture.
   *
   * Deletes the entire Drupal site build directory.
   *
   * @command fixture:destroy
   *
   * @return \Robo\ResultData
   */
  public function execute($opts = []) {
    $confirm = $this->confirm('Are you sure you want to destroy the test fixture?');
    if (!$confirm && !$opts['no-interaction']) {
      return Result::cancelled();
    }
    return $this->_deleteDir(self::BUILD_DIR);
  }

}
