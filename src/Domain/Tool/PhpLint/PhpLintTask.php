<?php

namespace Acquia\Orca\Domain\Tool\PhpLint;

use Acquia\Orca\Domain\Tool\TaskBase;

/**
 * Lints PHP files.
 */
class PhpLintTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->phpLintTool->label();
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return $this->phpLintTool->statusMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->phpLintTool->run($this->getPath());
  }

}
