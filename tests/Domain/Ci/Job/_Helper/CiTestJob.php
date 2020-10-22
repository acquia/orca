<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job\_Helper;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Options\CiRunOptions;
use Acquia\Orca\Tests\_Helper\TestSpy;

class CiTestJob extends AbstractCiJob {

  private $jobEnum;

  private $spy;

  public function __construct(TestSpy $spy) {
    $this->spy = $spy;
  }

  public function jobName(): CiJobEnum {
    return $this->jobEnum;
  }

  public function beforeInstall(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function install(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function beforeScript(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function script(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function beforeCache(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function afterSuccess(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function afterFailure(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function beforeDeploy(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function deploy(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function afterDeploy(CiRunOptions $options): void {
    $this->spy->call($options);
  }

  public function afterScript(CiRunOptions $options): void {
    $this->spy->call($options);
  }

}
