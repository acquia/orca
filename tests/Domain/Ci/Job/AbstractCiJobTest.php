<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Tests\_Helper\TestSpy;
use Acquia\Orca\Tests\Enum\CiEnumsTestTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class AbstractCiJobTest extends TestCase {

  use CiEnumsTestTrait;

  /**
   * @dataProvider providerPhases
   */
  public function testRun(CiJobPhaseEnum $phase): void {
    $spy = $this->prophesize(TestSpy::class);
    $spy
      ->call(Argument::any())
      ->shouldBeCalledOnce();
    $spy
      ->call($phase->getValue())
      ->shouldBeCalledOnce();
    $job = new CiTestJob($this->validJob(), $spy->reveal());
    $job->run($phase);
  }

}
