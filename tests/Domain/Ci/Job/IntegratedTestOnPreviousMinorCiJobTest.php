<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnPreviousMinorCiJob;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker|\Prophecy\Prophecy\ObjectProphecy
 *   $redundantJobChecker
 */
class IntegratedTestOnPreviousMinorCiJobTest extends CiJobTestBase {

  protected RedundantJobChecker|ObjectProphecy $redundantJobChecker;

  public function setUp(): void {
    $this->drupalCoreVersionResolver = $this->prophesize(DrupalCoreVersionResolver::class);
    $this->envFacade = $this->prophesize(EnvFacade::class);
    $this->output = $this->prophesize(OutputInterface::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  protected function createJob(): AbstractCiJob {
    $drupal_core_version_resolver = $this->drupalCoreVersionResolver->reveal();
    $env_facade = $this->envFacade->reveal();
    $output = $this->output->reveal();
    $process_runner = $this->processRunner->reveal();
    return new IntegratedTestOnPreviousMinorCiJob($drupal_core_version_resolver, $env_facade, $output, $process_runner);
  }

  public function testBasicConfiguration(): void {
    $job = $this->createJob();

    self::assertEquals(DrupalCoreVersionEnum::PREVIOUS_MINOR(), $job->getDrupalCoreVersion(), 'Declared the correct Drupal core version.');
  }

  public function testInstall(): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--core=PREVIOUS_MINOR',
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $this->runInstallPhase($job);
  }

  public function testNoDrupalCoreVersionFound(): void {
    $this->assertExitsEarlyIfNoDrupalCoreVersionFound();
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
        '--core=PREVIOUS_MINOR',
        "--profile={$profile}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job);
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
        '--core=PREVIOUS_MINOR',
        "--project-template={$project_template}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job);
  }

  public function testScript(): void {
    $this->processRunner
      ->runOrca(['fixture:status'])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runOrca([
        'qa:automated-tests',
        "--sut={$this->validSutName()}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runScriptPhase($job);
  }

  public function testScriptOverrideProfile(): void {
    $this->envFacade
      ->get('ORCA_FIXTURE_PROFILE')
      ->willReturn('test/example');
    $this->processRunner
      ->runOrca([
        'qa:automated-tests',
        "--sut={$this->validSutName()}",
        '--sut-only',
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runScriptPhase($job);
  }

}
