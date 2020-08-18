<?php

namespace Acquia\Orca\Task\StaticAnalysisTool;

use Acquia\Orca\Task\PhplocFacade;
use Acquia\Orca\Task\TaskInterface;

/**
 * Runs PHPLOC.
 */
class PhplocTask implements TaskInterface {

  public const JSON_LOG_PATH = 'var/log/phploc.json';

  /**
   * The PHPLOC facade.
   *
   * @var \Acquia\Orca\Task\PhplocFacade
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
   * @param \Acquia\Orca\Task\PhplocFacade $phploc_facade
   *   The PHPLOC facade.
   */
  public function __construct(PhplocFacade $phploc_facade) {
    $this->phploc = $phploc_facade;
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
