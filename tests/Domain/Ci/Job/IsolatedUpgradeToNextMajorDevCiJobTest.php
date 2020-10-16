<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeToNextMajorDevCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Console\Output\OutputInterface $symfonyOutput
 */
class IsolatedUpgradeToNextMajorDevCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->symfonyOutput = $this->prophesize(OutputInterface::class);
    parent::setUp();
  }

  private function createJob(): IsolatedUpgradeToNextMajorDevCiJob {
    $output = $this->symfonyOutput->reveal();
    return new IsolatedUpgradeToNextMajorDevCiJob($output);
  }

  public function testInstall(): void {
    $this->symfonyOutput
      ->writeln(Argument::any())
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::ISOLATED_UPGRADE_TO_NEXT_MAJOR_DEV,
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
      'job' => CiJobEnum::ISOLATED_UPGRADE_TO_NEXT_MAJOR_DEV,
      'phase' => CiJobPhaseEnum::SCRIPT,
      'sut' => $this->validSutName(),
    ]));
  }

}
