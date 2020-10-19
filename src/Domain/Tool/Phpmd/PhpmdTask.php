<?php

namespace Acquia\Orca\Domain\Tool\Phpmd;

use Acquia\Orca\Domain\Tool\TaskBase;

/**
 * Detects potential problems in PHP source code.
 */
class PhpmdTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->phpmdTool->label();
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return $this->phpmdTool->statusMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->phpmdTool->run($this->getPath());
  }

}
