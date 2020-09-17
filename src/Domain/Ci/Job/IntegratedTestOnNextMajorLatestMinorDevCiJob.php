<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The integrated test on next major, latest minor dev CI job.
 */
class IntegratedTestOnNextMajorLatestMinorDevCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_DEV();
  }

}
