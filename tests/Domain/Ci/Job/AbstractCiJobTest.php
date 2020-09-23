<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Options\CiRunOptions;
use Acquia\Orca\Tests\_Helper\TestSpy;
use Acquia\Orca\Tests\Enum\CiEnumsTestTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 */
class AbstractCiJobTest extends TestCase {

  use CiEnumsTestTrait;

  protected function setUp(): void {
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists($this->validSutName())
      ->willReturn(TRUE);
  }

  private function createCiRunOptions($options): CiRunOptions {
    $package_manager = $this->packageManager->reveal();
    return new CiRunOptions($package_manager, $options);
  }

  private function validSutName(): string {
    return 'drupal/example';
  }

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
