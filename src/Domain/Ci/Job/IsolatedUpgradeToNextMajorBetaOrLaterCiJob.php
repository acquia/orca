<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The isolated upgrade to next major beta-or-later CI job.
 */
class IsolatedUpgradeToNextMajorBetaOrLaterCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::ISOLATED_UPGRADE_TO_NEXT_MAJOR_BETA_OR_LATER();
  }

}
