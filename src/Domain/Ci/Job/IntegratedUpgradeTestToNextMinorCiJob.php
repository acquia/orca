<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The integrated upgrade test to next minor CI job.
 */
class IntegratedUpgradeTestToNextMinorCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR();
  }

}
