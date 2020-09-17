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
    // This unusual idiom is used here because it makes the the code coverage
    // generator happy, i.e., it doesn't report the logically unreachable
    // default case or the method's closing brace as uncovered.
    $functions = [
      CiJobPhaseEnum::BEFORE_INSTALL => function () {
        $this->beforeInstall();
      },
      CiJobPhaseEnum::INSTALL => function () {
        $this->install();
      },
      CiJobPhaseEnum::BEFORE_SCRIPT => function () {
        $this->beforeScript();
      },
      CiJobPhaseEnum::SCRIPT => function () {
        $this->script();
      },
      CiJobPhaseEnum::BEFORE_CACHE => function () {
        $this->beforeCache();
      },
      CiJobPhaseEnum::AFTER_SUCCESS => function () {
        $this->afterSuccess();
      },
      CiJobPhaseEnum::AFTER_FAILURE => function () {
        $this->afterFailure();
      },
      CiJobPhaseEnum::BEFORE_DEPLOY => function () {
        $this->beforeDeploy();
      },
      CiJobPhaseEnum::DEPLOY => function () {
        $this->deploy();
      },
      CiJobPhaseEnum::AFTER_DEPLOY => function () {
        $this->afterDeploy();
      },
      CiJobPhaseEnum::AFTER_SCRIPT => function () {
        $this->afterScript();
      },
    ];
    call_user_func($functions[$phase->getValue()]);
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
