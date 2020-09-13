<?php

namespace Acquia\Orca\Domain\Tool\Phploc;

use Acquia\Orca\Domain\Tool\TaskInterface;

/**
 * Runs PHPLOC.
 */
class PhplocTask implements TaskInterface {

  public const JSON_LOG_PATH = 'var/log/phploc.json';

  /**
   * The PHPLOC facade.
   *
   * @var \Acquia\Orca\Domain\Tool\Phploc\Phploc
   */
  private $phploc;

  /**
   * A filesystem path.
   *
   * @var string
   */
  private $path = '';

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Tool\Phploc\Phploc $phploc
   *   The PHPLOC facade.
   */
  public function __construct(Phploc $phploc) {
    $this->phploc = $phploc;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->phploc->execute($this->path);
  }

  /**
   * {@inheritdoc}
   */
  public function setPath(string $path): TaskInterface {
    $this->path = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'PHPLOC';
  }

  /**
   * {@inheritdoc}
   */
  public function statusMessage(): string {
    return 'Measuring the size of the PHP project';
  }

}
