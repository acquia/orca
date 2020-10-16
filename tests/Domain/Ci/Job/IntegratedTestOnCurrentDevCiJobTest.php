<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnCurrentDevCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;

/**
 * @coversDefaultClass \Acquia\Orca\Domain\Ci\Job\IntegratedTestOnCurrentDevCiJob
 */
class IntegratedTestOnCurrentDevCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  private function createJob(): IntegratedTestOnCurrentDevCiJob {
    $process_runner = $this->processRunner->reveal();
    return new IntegratedTestOnCurrentDevCiJob($process_runner);
  }

  public function testInstall(): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--core=CURRENT_DEV',
        '--dev',
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::INTEGRATED_TEST_ON_CURRENT_DEV,
      'phase' => CiJobPhaseEnum::INSTALL,
      'sut' => $this->validSutName(),
    ]));
  }

  public function testScript(): void {
    $this->processRunner
      ->runOrca(['fixture:status'])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $this->processRunner
      ->runOrca([
        'qa:automated-tests',
        "--sut={$this->validSutName()}",
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $job->run($this->createValidRunOptions());
  }

}
