<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Robo\Result;

/**
 * Provides the "destroy" command.
 */
class DestroyCommand extends CommandBase {

  /**
   * Destroys the build
   *
   * @command destroy
   *
   * @param array $opts
   *
   * @return \Robo\ResultData
   */
  public function execute($opts = ['build-directory|d' => '../build']) {
    $this->commandOptions = $opts;
    $confirm = $this->confirm(sprintf('Are you sure you want to destroy the build at %s?', $this->getBuildDir()));
    if (!$confirm && !$opts['no-interaction']) {
      return Result::cancelled();
    }
    return $this->_deleteDir($this->getBuildDir());
  }

}
