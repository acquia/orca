<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Tests\_Helper\TestSpy;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiTestJob;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @coversDefaultClass \Acquia\Orca\Domain\Ci\Job\AbstractCiJob
 */
class AbstractCiJobTest extends CiJobTestBase {

  /**
   * @dataProvider providerJobs
   */
  public function testRunJobs(CiJobEnum $job): void {
    $options = $this->createCiRunOptions([
      'job' => $job->getValue(),
      'phase' => $this->validPhaseName(),
      'sut' => $this->validSutName(),
    ]);
    $spy = $this->prophesize(TestSpy::class);
    $spy
      ->call(Argument::any())
      ->shouldBeCalledOnce();
    $spy
      ->call($options)
      ->shouldBeCalledOnce();
    $job = new CiTestJob($options, $spy->reveal());

    $job->run($options);
  }

  /**
   * @dataProvider providerPhases
   */
  public function testRunPhases(CiJobPhaseEnum $phase): void {
    $options = $this->createCiRunOptions([
      'job' => $this->validJobName(),
      'phase' => $phase->getValue(),
      'sut' => $this->validSutName(),
    ]);
    $spy = $this->prophesize(TestSpy::class);
    $spy
      ->call($options)
      ->shouldBeCalledOnce();
    $job = new CiTestJob($options, $spy->reveal());

    $job->run($options);
  }

}
