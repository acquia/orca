<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The integrated test on next minor CI job.
 */
class IntegratedTestOnNextMinorCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_TEST_ON_NEXT_MINOR();
  }

}
