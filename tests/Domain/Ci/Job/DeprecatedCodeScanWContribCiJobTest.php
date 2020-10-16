<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\DeprecatedCodeScanWContribCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;

/**
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
class DeprecatedCodeScanWContribCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  private function createJob(): DeprecatedCodeScanWContribCiJob {
    return new DeprecatedCodeScanWContribCiJob($this->processRunner->reveal());
  }

  public function testInstall(): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--core=CURRENT_DEV',
        '--no-site-install',
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::DEPRECATED_CODE_SCAN_W_CONTRIB,
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
        'qa:deprecated-code-scan',
        '--contrib',
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::DEPRECATED_CODE_SCAN_W_CONTRIB,
      'phase' => CiJobPhaseEnum::SCRIPT,
      'sut' => $this->validSutName(),
    ]));
  }

}
