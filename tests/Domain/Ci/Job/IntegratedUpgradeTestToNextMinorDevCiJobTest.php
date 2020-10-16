<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\IntegratedUpgradeTestToNextMinorDevCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Console\Output\OutputInterface $symfonyOutput
 */
class IntegratedUpgradeTestToNextMinorDevCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->symfonyOutput = $this->prophesize(OutputInterface::class);
    parent::setUp();
  }

  private function createJob(): IntegratedUpgradeTestToNextMinorDevCiJob {
    $output = $this->symfonyOutput->reveal();
    return new IntegratedUpgradeTestToNextMinorDevCiJob($output);
  }

  public function testInstall(): void {
    $this->symfonyOutput
      ->writeln(Argument::any())
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR_DEV,
      'phase' => CiJobPhaseEnum::INSTALL,
      'sut' => $this->validSutName(),
    ]));
  }

  public function testScript(): void {
    $this->symfonyOutput
      ->writeln(Argument::any())
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR_DEV,
      'phase' => CiJobPhaseEnum::SCRIPT,
      'sut' => $this->validSutName(),
    ]));
  }

}
