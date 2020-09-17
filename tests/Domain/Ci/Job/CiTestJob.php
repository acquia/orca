<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Tests\_Helper\TestSpy;

class CiTestJob extends AbstractCiJob {

  private $jobEnum;

  private $spy;

  public function __construct(CiJobEnum $job_enum, TestSpy $spy) {
    $this->jobEnum = $job_enum;
    $this->spy = $spy;
  }

  public function jobName(): CiJobEnum {
    return $this->jobEnum;
  }

  public function beforeInstall(): void {
    $this->spy->call(CiJobPhaseEnum::BEFORE_INSTALL);
  }

  public function install(): void {
    $this->spy->call(CiJobPhaseEnum::INSTALL);
  }

  public function beforeScript(): void {
    $this->spy->call(CiJobPhaseEnum::BEFORE_SCRIPT);
  }

  public function script(): void {
    $this->spy->call(CiJobPhaseEnum::SCRIPT);
  }

  public function beforeCache(): void {
    $this->spy->call(CiJobPhaseEnum::BEFORE_CACHE);
  }

  public function afterSuccess(): void {
    $this->spy->call(CiJobPhaseEnum::AFTER_SUCCESS);
  }

  public function afterFailure(): void {
    $this->spy->call(CiJobPhaseEnum::AFTER_FAILURE);
  }

  public function beforeDeploy(): void {
    $this->spy->call(CiJobPhaseEnum::BEFORE_DEPLOY);
  }

  public function deploy(): void {
    $this->spy->call(CiJobPhaseEnum::DEPLOY);
  }

  public function afterDeploy(): void {
    $this->spy->call(CiJobPhaseEnum::AFTER_DEPLOY);
  }

  public function afterScript(): void {
    $this->spy->call(CiJobPhaseEnum::AFTER_SCRIPT);
  }

}
