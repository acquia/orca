<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker;
use Acquia\Orca\Domain\Ci\Job\IntegratedTestOnOldestSupportedCiJob;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\DrupalCoreVersionEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker|\Prophecy\Prophecy\ObjectProphecy $redundantJobChecker
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
class IntegratedTestOnOldestSupportedCiJobTest extends CiJobTestBase {

  protected RedundantJobChecker|ObjectProphecy $redundantJobChecker;
  protected PackageManager|ObjectProphecy $packageManager;
  protected ProcessRunner|ObjectProphecy $processRunner;

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

  protected function createJob(): AbstractCiJob {
    $env_facade = $this->envFacade->reveal();
    $output = $this->output->reveal();
    $process_runner = $this->processRunner->reveal();
    $redundant_job_checker = $this->redundantJobChecker->reveal();
    return new IntegratedTestOnOldestSupportedCiJob($env_facade, $output, $process_runner, $redundant_job_checker);
  }

  public function testBasicConfiguration(): void {
    $job = $this->createJob();

    self::assertEquals(DrupalCoreVersionEnum::OLDEST_SUPPORTED(), $job->getDrupalCoreVersion(), 'Declared the correct Drupal core version.');
  }

  public function testInstall(): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$this->validSutName()}",
        '--core=OLDEST_SUPPORTED',
      ])
      ->shouldBeCalledOnce()
      ->willReturn(0);
    $job = $this->createJob();

    $this->runInstallPhase($job);
  }

  public function testRedundantJob(): void {
    $this->redundantJobChecker
      ->isRedundant(CiJobEnum::INTEGRATED_TEST_ON_OLDEST_SUPPORTED())
      ->willReturn(TRUE);
    $this->output
      ->writeln(Argument::any())
      ->shouldBeCalledTimes(2);
    $this->processRunner
      ->runOrca(Argument::any())
      ->shouldNotBeCalled();
    $job = $this->createJob();

    $this->runScriptPhase($job);
    $this->runScriptPhase($job);
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
        '--core=OLDEST_SUPPORTED',
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
        '--core=OLDEST_SUPPORTED',
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
