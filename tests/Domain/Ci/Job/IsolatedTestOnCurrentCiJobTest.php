<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;

/**
 * @coversDefaultClass \Acquia\Orca\Domain\Ci\Job\IsolatedTestOnCurrentCiJob
 */
class IsolatedTestOnCurrentCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  private function createJob(): IsolatedTestOnCurrentCiJob {
    $process_runner = $this->processRunner->reveal();
    return new IsolatedTestOnCurrentCiJob($process_runner);
  }

  public function testInstall(): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        "--sut={$this->validSutName()}",
        '--sut-only',
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::INTEGRATED_TEST_ON_CURRENT,
      'phase' => CiJobPhaseEnum::INSTALL,
      'sut' => $this->validSutName(),
    ]));
  }

  public function testScript(): void {
    $this->processRunner
      ->runOrca([
        'qa:automated-tests',
        "--sut={$this->validSutName()}",
        '--sut-only',
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $job->run($this->createValidRunOptions());
  }

}
