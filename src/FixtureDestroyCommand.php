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
   * @param array $opts
   *
   * @return \Robo\ResultData
   */
  public function execute($opts = ['build-directory|d' => '../build']) {
    $this->commandOptions = $opts;
    $confirm = $this->confirm(sprintf('Are you sure you want to destroy the test fixture at %s?', $this->getBuildDir()));
    if (!$confirm && !$opts['no-interaction']) {
      return Result::cancelled();
    }
    return $this->_deleteDir($this->getBuildDir());
  }

}
