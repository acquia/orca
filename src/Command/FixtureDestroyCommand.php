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
   * Deletes the entire site build directory.
   *
   * @command fixture:destroy
   * @aliases destroy
   *
   * @return \Robo\Result|int
   */
  public function execute($opts = []) {
    $confirm = $this->confirm('Are you sure you want to destroy the test fixture?');
    if (!$confirm && !$opts['no-interaction']) {
      return Result::EXITCODE_USER_CANCEL;
    }

    return $this->_deleteDir($this->buildPath());
  }

}
