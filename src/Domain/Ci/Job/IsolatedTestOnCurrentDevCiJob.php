<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The isolated test on current dev CI job.
 */
class IsolatedTestOnCurrentDevCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::ISOLATED_TEST_ON_CURRENT_DEV();
  }

}
