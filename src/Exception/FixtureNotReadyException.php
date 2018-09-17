<?php

namespace AcquiaOrca\Exception;

use Symfony\Component\Console\Style\StyleInterface;

/**
 * Exception thrown if the ORCA fixture is not ready.
 */
class FixtureNotReadyException extends \RuntimeException {

  protected $message = 'The fixture is not ready. Run `orca fixture:create` first.';

  /**
   * {@inheritdoc}
   */
  public function __construct(StyleInterface $io) {
    $io->error($this->message);
    parent::__construct($this->message);
  }

}
