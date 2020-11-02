<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job\_Helper;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Exception\OrcaVersionNotFoundException;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver|\Prophecy\Prophecy\ObjectProphecy $drupalCoreVersionResolver
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\EnvFacade|\Prophecy\Prophecy\ObjectProphecy $envFacade
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @property \Symfony\Component\Console\Output\OutputInterface|\Prophecy\Prophecy\ObjectProphecy $output
 */
abstract class CiJobTestBase extends TestCase {

  protected const SUT_REPOSITORY_URL_ABSOLUTE = '/var/www/sut';

  public function setUp(): void {
    // Drupal core version resolver.
    $this->drupalCoreVersionResolver = $this->prophesize(DrupalCoreVersionResolver::class);
    $this->drupalCoreVersionResolver
      ->resolvePredefined(Argument::any())
      ->willReturn('9.0.0');

    // ENV facade.
    $this->envFacade = $this->prophesize(EnvFacade::class);
    $this->envFacade
      ->get(Argument::any())
      ->willReturn(NULL);

    // SUT (package).
    $sut = $this->prophesize(Package::class);
    $sut->getPackageName()
      ->willReturn($this->validSutName());
    $sut->getRepositoryUrlAbsolute()
      ->willReturn(self::SUT_REPOSITORY_URL_ABSOLUTE);

    // Output decorator.
    $this->output = $this->prophesize(OutputInterface::class);
    $this->output
      ->writeln(Argument::any())
      ->shouldNotBeCalled();

    // Package manager.
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->exists($this->validSutName())
      ->willReturn(TRUE);
    $this->packageManager
      ->get($this->validSutName())
      ->willReturn($sut);

    // Process runner.
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->runOrca(Argument::any())
      ->willReturn(0);
  }

  protected function createJob(): AbstractCiJob {
    return new class($this->validJobName()) extends AbstractCiJob {

      public function __construct(CiJobEnum $job_name) {
        $this->jobName = $job_name;
      }

      protected function jobName(): CiJobEnum {
        return $this->jobName;
      }

    };
  }

  protected function assertExitsEarlyIfNoDrupalCoreVersionFound(): void {
    $job = $this->createJob();
    $this->drupalCoreVersionResolver
      ->resolvePredefined($job->getDrupalCoreVersion())
      ->shouldBeCalledTimes(2)
      ->willThrow(OrcaVersionNotFoundException::class);
    $this->output
      ->writeln(Argument::any())
      ->shouldBeCalledTimes(2);
    $this->processRunner
      ->runOrca(Argument::any())
      ->shouldNotBeCalled();

    $this->runInstallPhase($job);
    $this->runScriptPhase($job);
  }

  protected function createCiRunOptions($options): CiRunOptions {
    $package_manager = $this->packageManager->reveal();
    return new CiRunOptions($package_manager, $options);
  }

  protected function createValidRunOptions(): CiRunOptions {
    return $this->createCiRunOptions($this->validRawOptions());
  }

  protected function validRawOptions(): array {
    return [
      'job' => $this->validJobName(),
      'phase' => $this->validPhaseName(),
      'sut' => $this->validSutName(),
    ];
  }

  protected function validSutName(): string {
    return 'drupal/example';
  }

  public function providerJobs(): array {
    $jobs = CiJobEnum::values();
    array_walk($jobs, static function (&$value) {
      $value = [$value];
    });
    return $jobs;
  }

  public function providerPhases(): array {
    $phases = CiJobPhaseEnum::values();
    array_walk($phases, static function (&$value) {
      $value = [$value];
    });
    return $phases;
  }

  protected function runInstallPhase(AbstractCiJob $job): void {
    $job->run($this->createCiRunOptions([
      'job' => $job->getJobName()->getKey(),
      'phase' => CiJobPhaseEnum::INSTALL,
      'sut' => $this->validSutName(),
    ]));
  }

  protected function runScriptPhase(AbstractCiJob $job): void {
    $job->run($this->createCiRunOptions([
      'job' => $job->getJobName()->getKey(),
      'phase' => CiJobPhaseEnum::SCRIPT,
      'sut' => $this->validSutName(),
    ]));
  }

  protected function validJob(): CiJobEnum {
    $jobs = CiJobEnum::values();
    return reset($jobs);
  }

  protected function validJobName(): string {
    return $this->validJob()->getKey();
  }

  protected function validPhase(): CiJobPhaseEnum {
    return CiJobPhaseEnum::SCRIPT();
  }

  protected function validPhaseName(): string {
    return strtolower($this->validPhase()->getValue());
  }

}
