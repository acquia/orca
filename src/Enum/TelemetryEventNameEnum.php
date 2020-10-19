<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides telemetry event names.
 *
 * @method static TelemetryEventNameEnum TEST()
 * @method static TelemetryEventNameEnum TRAVIS_CI_JOB()
 */
class TelemetryEventNameEnum extends Enum {

  public const TRAVIS_CI_JOB = 'Travis CI job run';

  public const TEST = 'Telemetry test run';

}
