<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides Drupal core version special values.
 */
final class DrupalCoreVersion extends Enum {

  public const PREVIOUS_RELEASE = 'PREVIOUS_RELEASE';

  public const PREVIOUS_DEV = 'PREVIOUS_DEV';

  public const CURRENT_RECOMMENDED = 'CURRENT_RECOMMENDED';

  public const CURRENT_DEV = 'CURRENT_DEV';

  public const NEXT_RELEASE = 'NEXT_RELEASE';

  public const NEXT_DEV = 'NEXT_DEV';

}
