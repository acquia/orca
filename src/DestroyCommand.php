<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

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
   * @return \Robo\Result
   */
  public function execute($opts = ['build-directory|d' => '../build']) {
    $this->commandOptions = $opts;
    return $this->_deleteDir($this->getBuildDir());
  }

}
