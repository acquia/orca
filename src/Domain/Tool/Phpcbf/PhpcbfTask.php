<?php

namespace Acquia\Orca\Domain\Tool\Phpcbf;

use Acquia\Orca\Domain\Tool\TaskBase;
use Acquia\Orca\Enum\PhpcsStandardEnum;

/**
 * Automatically fixes coding standards violations.
 */
class PhpcbfTask extends TaskBase {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return $this->phpcbfTool->label();
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return $this->phpcbfTool->statusMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $path = realpath($this->getPath());
    $this->phpcbfTool->run($path);
  }

  /**
   * Sets the standard to use.
   *
   * @param \Acquia\Orca\Enum\PhpcsStandardEnum $standard
   *   The PHPCS standard.
   */
  public function setStandard(PhpcsStandardEnum $standard): void {
    $this->phpcbfTool->setStandard($standard);
  }

}
