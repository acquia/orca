<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The isolated test on next major, latest minor dev CI job.
 */
class IsolatedTestOnNextMajorLatestMinorDevCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV();
  }

}
