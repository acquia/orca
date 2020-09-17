<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The loose deprecated code scan CI job.
 */
class LooseDeprecatedCodeScanCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::LOOSE_DEPRECATED_CODE_SCAN();
  }

}
