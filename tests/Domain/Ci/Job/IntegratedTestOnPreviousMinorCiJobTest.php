<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnPreviousMinorCiJob;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker|\Prophecy\Prophecy\ObjectProphecy $redundantJobChecker
 */
class IntegratedTestOnPreviousMinorCiJobTest extends CiJobTestBase {

  public function setUp(): void {
    $this->envFacade = $this->prophesize(EnvFacade::class);
    $this->output = $this->prophesize(OutputInterface::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->redundantJobChecker = $this->prophesize(RedundantJobChecker::class);
    $this->redundantJobChecker
      ->isRedundant(Argument::any())
      ->willReturn(FALSE);
    parent::setUp();
  }

  private function createJob(): IntegratedTestOnPreviousMinorCiJob {
    $env_facade = $this->envFacade->reveal();
    $output = $this->output->reveal();
    $process_runner = $this->processRunner->reveal();
    $redundant_job_checker = $this->redundantJobChecker->reveal();
    return new IntegratedTestOnPreviousMinorCiJob($env_facade, $output, $process_runner, $redundant_job_checker);
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

    $this->runInstallPhase($job, CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR);
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

    $this->runInstallPhase($job, CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR);
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

    $this->runInstallPhase($job, CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR);
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

  public function testRedundantJob(): void {
    $this->redundantJobChecker
      ->isRedundant(CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR())
      ->willReturn(TRUE);
    $this->output
      ->writeln(Argument::any())
      ->shouldBeCalledTimes(2);
    $this->processRunner
      ->runOrca(Argument::any())
      ->shouldNotBeCalled();
    $job = $this->createJob();

    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR,
      'phase' => CiJobPhaseEnum::INSTALL,
      'sut' => $this->validSutName(),
    ]));
    $job->run($this->createCiRunOptions([
      'job' => CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR,
      'phase' => CiJobPhaseEnum::SCRIPT,
      'sut' => $this->validSutName(),
    ]));
  }

}
