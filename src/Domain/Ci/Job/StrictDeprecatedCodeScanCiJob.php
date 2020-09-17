<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The strict deprecated code scan CI job.
 */
class StrictDeprecatedCodeScanCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::STRICT_DEPRECATED_CODE_SCAN();
  }

}
