<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Domain\Ci\Job\IsolatedUpgradeTestToNextMajorBetaOrLaterCiJob;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Console\Output\OutputInterface $symfonyOutput
 */
class IsolatedUpgradeToNextMajorBetaOrLaterCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->symfonyOutput = $this->prophesize(OutputInterface::class);
    parent::setUp();
  }

  protected function createJob(): AbstractCiJob {
    $output = $this->symfonyOutput->reveal();
    return new IsolatedUpgradeTestToNextMajorBetaOrLaterCiJob($output);
  }

  public function testBasicConfiguration(): void {
    $job = $this->createJob();

    self::assertEquals(DrupalCoreVersionEnum::CURRENT(), $job->getDrupalCoreVersion(), 'Declared the correct Drupal core version.');
  }

  public function testInstall(): void {
    $this->symfonyOutput
      ->writeln(Argument::any())
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job);
  }

  public function testScript(): void {
    $this->symfonyOutput
      ->writeln(Argument::any())
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runScriptPhase($job);
  }

}
