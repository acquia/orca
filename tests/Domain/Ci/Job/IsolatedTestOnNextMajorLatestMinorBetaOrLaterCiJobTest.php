<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

class IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->drupalCoreVersionResolver = $this->prophesize(DrupalCoreVersionResolver::class);
    $this->envFacade = $this->prophesize(EnvFacade::class);
    $this->output = $this->prophesize(OutputInterface::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    parent::setUp();
  }

  private function createJob(): IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob {
    $drupal_core_version_resolver = $this->drupalCoreVersionResolver->reveal();
    $env_facade = $this->envFacade->reveal();
    $output = $this->output->reveal();
    $process_runner = $this->processRunner->reveal();
    return new IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob($drupal_core_version_resolver, $env_facade, $output, $process_runner);
  }

  public function testBasicConfiguration(): void {
    $job = $this->createJob();

    self::assertEquals(DrupalCoreVersionEnum::NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER(), $job->getDrupalCoreVersion(), 'Declared the correct Drupal core version.');
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

    $this->runInstallPhase($job, CiJobEnum::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER);
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
        '--sut-only',
        '--core=NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER',
        "--profile={$profile}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job, CiJobEnum::INTEGRATED_TEST_ON_CURRENT_DEV);
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
        '--sut-only',
        '--core=NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER',
        "--project-template={$project_template}",
      ])
      ->shouldBeCalledOnce();
    $job = $this->createJob();

    $this->runInstallPhase($job, CiJobEnum::INTEGRATED_TEST_ON_CURRENT_DEV);
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

  public function testNoDrupalCoreVersionFound(): void {
    $this->drupalCoreVersionResolver
      ->resolvePredefined(CiJobEnum::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER()->getDrupalCoreVersion())
      ->shouldBeCalledTimes(2)
      ->willThrow(OrcaVersionNotFoundException::class);
    $this->output
      ->writeln(Argument::any())
      ->shouldBeCalledTimes(2);
    $this->processRunner
      ->runOrca(Argument::any())
      ->shouldNotBeCalled();
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER,
      'phase' => CiJobPhaseEnum::INSTALL,
      'sut' => $this->validSutName(),
    ]));
    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER,
      'phase' => CiJobPhaseEnum::SCRIPT,
      'sut' => $this->validSutName(),
    ]));
  }

}
