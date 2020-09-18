<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;

/**
 * Provides an abstract class for CI Job classes.
 */
abstract class AbstractCiJob {

  /**
   * Gets the job name.
   *
   * @return string
   *   The job name.
   */
  final public function getJobName(): string {
    return $this->jobName()->getValue();
  }

  /**
   * Declares the job name.
   *
   * @return \Acquia\Orca\Enum\CiJobEnum
   *   The job enum.
   */
  abstract protected function jobName(): CiJobEnum;

  /**
   * Runs a job given job.
   *
   * @param \Acquia\Orca\Enum\CiJobPhaseEnum $phase
   *   The CI job phase.
   */
  final public function run(CiJobPhaseEnum $phase): void {
    switch ($phase) {
      case CiJobPhaseEnum::BEFORE_INSTALL:
        $this->beforeInstall();
        break;

      case CiJobPhaseEnum::INSTALL:
        $this->install();
        break;

      case CiJobPhaseEnum::BEFORE_SCRIPT:
        $this->beforeScript();
        break;

      case CiJobPhaseEnum::SCRIPT:
        $this->script();
        break;

      case CiJobPhaseEnum::BEFORE_CACHE:
        $this->beforeCache();
        break;

      case CiJobPhaseEnum::AFTER_SUCCESS:
        $this->afterSuccess();
        break;

      case CiJobPhaseEnum::AFTER_FAILURE:
        $this->afterFailure();
        break;

      case CiJobPhaseEnum::BEFORE_DEPLOY:
        $this->beforeDeploy();
        break;

      case CiJobPhaseEnum::DEPLOY:
        $this->deploy();
        break;

      case CiJobPhaseEnum::AFTER_DEPLOY:
        $this->afterDeploy();
        break;

      case CiJobPhaseEnum::AFTER_SCRIPT:
        $this->afterScript();

    }
  }

  /**
   * Runs before the install stage.
   */
  protected function beforeInstall(): void {}

  /**
   * Runs at the install stage.
   */
  protected function install(): void {}

  /**
   * Runs before the script stage.
   */
  protected function beforeScript(): void {}

  /**
   * Runs at the script stage.
   */
  protected function script(): void {}

  /**
   * Runs before storing a build cache.
   */
  protected function beforeCache(): void {}

  /**
   * Runs after a successful script stage.
   */
  protected function afterSuccess(): void {}

  /**
   * Runs after a failing script stage.
   */
  protected function afterFailure(): void {}

  /**
   * Runs before the deploy stage.
   */
  protected function beforeDeploy(): void {}

  /**
   * Runs at the deploy stage.
   */
  protected function deploy(): void {}

  /**
   * Runs after the deploy stage.
   */
  protected function afterDeploy(): void {}

  /**
   * Runs as the last stage.
   */
  protected function afterScript(): void {}

}
