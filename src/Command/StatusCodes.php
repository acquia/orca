<?php

namespace Acquia\Orca\Command;

/**
 * Defines sysexits compatible status codes.
 *
 * @see https://www.freebsd.org/cgi/man.cgi?query=sysexits
 */
final class StatusCodes {

  public const OK = 0;

  public const ERROR = 1;

  public const USER_CANCEL = 75;

}
