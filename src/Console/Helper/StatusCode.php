<?php

namespace Acquia\Orca\Console\Helper;

use MyCLabs\Enum\Enum;

/**
 * Defines sysexits compatible status codes.
 *
 * @see https://www.freebsd.org/cgi/man.cgi?query=sysexits
 */
final class StatusCode extends Enum {

  public const OK = 0;

  public const ERROR = 1;

  public const USER_CANCEL = 75;

}
