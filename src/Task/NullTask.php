<?php

namespace Acquia\Orca\Task;

use Robo\Contract\TaskInterface;
use Robo\Result;

/**
 * Provides a task that does nothing.
 */
class NullTask implements TaskInterface {

  /**
   * {@inheritdoc}
   */
  public function run() {
    return new Result($this, Result::EXITCODE_OK);
  }

}
