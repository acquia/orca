<?php

namespace AcquiaOrca\Exception;

/**
 * Exception thrown if the ORCA fixture is not ready.
 */
class FixtureNotReadyException extends \RuntimeException {

  protected $message = 'The fixture is not ready. Run `orca fixture:create` first.';

}
