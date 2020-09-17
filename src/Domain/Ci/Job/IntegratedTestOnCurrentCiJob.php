<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The integrated test on current CI job.
 */
class IntegratedTestOnCurrentCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_TEST_ON_CURRENT();
  }

}
