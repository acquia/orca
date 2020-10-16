<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;

/**
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
class IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  private function createJob(): IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob {
    return new IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob($this->processRunner->reveal());
  }

  public function testInstall(): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--sut-only',
        '--core=NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER',
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER,
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
        '--sut-only',
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $job->run($this->createValidRunOptions());
  }

}
