<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The isolated upgrade to next major dev CI job.
 */
class IsolatedUpgradeToNextMajorDevCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::ISOLATED_UPGRADE_TO_NEXT_MAJOR_DEV();
  }

}
