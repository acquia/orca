<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnNextMinorDevCiJob;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;

class IntegratedTestOnNextMinorDevCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->envFacade = $this->prophesize(EnvFacade::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  private function createJob(): IntegratedTestOnNextMinorDevCiJob {
    $env_facade = $this->envFacade->reveal();
    $process_runner = $this->processRunner->reveal();
    return new IntegratedTestOnNextMinorDevCiJob($env_facade, $process_runner);
  }

  public function testBasicConfiguration(): void {
    $job = $this->createJob();

    self::assertEquals(DrupalCoreVersionEnum::NEXT_MINOR_DEV(), $job->getDrupalCoreVersion(), 'Declared the correct Drupal core version.');
  }

  public function testInstall(): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--core=NEXT_MINOR_DEV',
        '--dev',
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $this->runInstallPhase($job, CiJobEnum::INTEGRATED_TEST_ON_NEXT_MINOR_DEV);
  }

  public function testInstallOverrideProfile(): void {
    $profile = 'example';
    $this->envFacade
      ->get('ORCA_FIXTURE_PROFILE')
      ->willReturn($profile);
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--core=NEXT_MINOR_DEV',
        '--dev',
        "--profile={$profile}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job, CiJobEnum::INTEGRATED_TEST_ON_NEXT_MINOR_DEV);
  }

  public function testInstallOverrideProjectTemplate(): void {
    $project_template = 'example';
    $this->envFacade
      ->get('ORCA_FIXTURE_PROJECT_TEMPLATE')
      ->willReturn($project_template);
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--core=NEXT_MINOR_DEV',
        '--dev',
        "--project-template={$project_template}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job, CiJobEnum::INTEGRATED_TEST_ON_NEXT_MINOR_DEV);
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
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $job->run($this->createValidRunOptions());
  }

}
