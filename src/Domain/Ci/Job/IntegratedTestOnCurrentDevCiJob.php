<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The integrated test on current dev CI job.
 */
class IntegratedTestOnCurrentDevCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_TEST_ON_CURRENT_DEV();
  }

}
