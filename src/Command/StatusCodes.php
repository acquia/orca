<?php

namespace Acquia\Orca\Command;

/**
 * Defines sysexits compatible status codes.
 *
 * @see https://www.freebsd.org/cgi/man.cgi?query=sysexits
 */
final class StatusCodes {

  const OK = 0;

  const ERROR = 1;

  const USER_CANCEL = 75;

}
