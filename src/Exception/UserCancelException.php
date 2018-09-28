<?php

namespace Acquia\Orca\Exception;

use Robo\Result;

/**
 * Exception thrown if the user cancels the command.
 */
class UserCancelException extends \RuntimeException {

  protected $code = Result::EXITCODE_USER_CANCEL;

}
