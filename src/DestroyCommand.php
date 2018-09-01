<?php

namespace AcquiaOrca\Robo\Plugin\Commands;

use Composer\Config\JsonConfigSource;
use Composer\Json\JsonFile;
use Robo\ResultData;

/**
 * Provides the "destroy" command.
 */
class DestroyCommand extends CommandBase {

  /**
   * Destroys the build
   *
   * @command destroy
   */
  public function execute() {
    return $this->_deleteDir(self::BUILD_DIR);
  }

}
