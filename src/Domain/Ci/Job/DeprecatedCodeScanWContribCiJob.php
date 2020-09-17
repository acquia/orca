<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;

/**
 * The deprecated code scan w/ contrib CI job.
 */
class DeprecatedCodeScanWContribCiJob extends AbstractCiJob {

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::DEPRECATED_CODE_SCAN_W_CONTRIB();
  }

}
