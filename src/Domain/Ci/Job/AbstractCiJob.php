<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Options\CiRunOptions;

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
   * Runs a given job.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  final public function run(CiRunOptions $options): void {
    switch ($options->getPhase()->getValue()) {
      case CiJobPhaseEnum::BEFORE_INSTALL:
        $this->beforeInstall($options);
        break;

      case CiJobPhaseEnum::INSTALL:
        $this->install($options);
        break;

      case CiJobPhaseEnum::BEFORE_SCRIPT:
        $this->beforeScript($options);
        break;

      case CiJobPhaseEnum::SCRIPT:
        $this->script($options);
        break;

      case CiJobPhaseEnum::BEFORE_CACHE:
        $this->beforeCache($options);
        break;

      case CiJobPhaseEnum::AFTER_SUCCESS:
        $this->afterSuccess($options);
        break;

      case CiJobPhaseEnum::AFTER_FAILURE:
        $this->afterFailure($options);
        break;

      case CiJobPhaseEnum::BEFORE_DEPLOY:
        $this->beforeDeploy($options);
        break;

      case CiJobPhaseEnum::DEPLOY:
        $this->deploy($options);
        break;

      case CiJobPhaseEnum::AFTER_DEPLOY:
        $this->afterDeploy($options);
        break;

      case CiJobPhaseEnum::AFTER_SCRIPT:
        $this->afterScript($options);

    }
  }

  /**
   * Runs before the install stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function beforeInstall(CiRunOptions $options): void {}

  /**
   * Runs at the install stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function install(CiRunOptions $options): void {}

  /**
   * Runs before the script stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function beforeScript(CiRunOptions $options): void {}

  /**
   * Runs at the script stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function script(CiRunOptions $options): void {}

  /**
   * Runs before storing a build cache.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function beforeCache(CiRunOptions $options): void {}

  /**
   * Runs after a successful script stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function afterSuccess(CiRunOptions $options): void {}

  /**
   * Runs after a failing script stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function afterFailure(CiRunOptions $options): void {}

  /**
   * Runs before the deploy stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function beforeDeploy(CiRunOptions $options): void {}

  /**
   * Runs at the deploy stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function deploy(CiRunOptions $options): void {}

  /**
   * Runs after the deploy stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function afterDeploy(CiRunOptions $options): void {}

  /**
   * Runs as the last stage.
   *
   * @param \Acquia\Orca\Options\CiRunOptions $options
   *   The CI run options.
   */
  protected function afterScript(CiRunOptions $options): void {}

}
