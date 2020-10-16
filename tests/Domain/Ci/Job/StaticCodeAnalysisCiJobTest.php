<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\StaticCodeAnalysisCiJob;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;

/**
 * @coversDefaultClass StaticCodeAnalysisCiJob
 */
class StaticCodeAnalysisCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  private function createJob(): StaticCodeAnalysisCiJob {
    $process_runner = $this->processRunner->reveal();
    return new StaticCodeAnalysisCiJob($process_runner);
  }

  public function testScript(): void {
    $this->processRunner
      ->runOrca(['fixture:status'])
      ->shouldNotBeCalled();
    $job = $this->createJob();
    $this->processRunner
      ->runOrca([
        'qa:static-analysis',
        self::SUT_REPOSITORY_URL_ABSOLUTE,
      ])
      ->shouldBeCalledOnce();

    $job->run($this->createValidRunOptions());
  }

}
