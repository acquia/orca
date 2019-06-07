<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides Drupal core version special values.
 *
 * @method static DrupalCoreVersion PREVIOUS_RELEASE()
 * @method static DrupalCoreVersion PREVIOUS_DEV()
 * @method static DrupalCoreVersion CURRENT_RECOMMENDED()
 * @method static DrupalCoreVersion CURRENT_DEV()
 * @method static DrupalCoreVersion NEXT_RELEASE()
 * @method static DrupalCoreVersion NEXT_DEV()
 */
final class DrupalCoreVersion extends Enum {

  private const PREVIOUS_RELEASE = 'PREVIOUS_RELEASE';

  private const PREVIOUS_DEV = 'PREVIOUS_DEV';

  private const CURRENT_RECOMMENDED = 'CURRENT_RECOMMENDED';

  private const CURRENT_DEV = 'CURRENT_DEV';

  private const NEXT_RELEASE = 'NEXT_RELEASE';

  private const NEXT_DEV = 'NEXT_DEV';

}
