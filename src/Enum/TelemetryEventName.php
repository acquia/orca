<?php

namespace Acquia\Orca\Enum;

use MyCLabs\Enum\Enum;

/**
 * Provides telemetry event names.
 *
 * @method static TelemetryEventName TEST()
 * @method static TelemetryEventName TRAVIS_CI_JOB()
 */
class TelemetryEventName extends Enum {

  public const TRAVIS_CI_JOB = 'Travis CI job run';

  public const TEST = 'Telemetry test run';

}
