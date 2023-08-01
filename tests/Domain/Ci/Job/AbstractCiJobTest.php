<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\AbstractCiJob;
use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Domain\Fixture\FixtureCreator;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\_Helper\TestSpy;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiTestJob;
use Prophecy\Argument;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @coversDefaultClass \Acquia\Orca\Domain\Ci\Job\AbstractCiJob
 */
class AbstractCiJobTest extends CiJobTestBase {

  /**
   * @dataProvider providerJobs
   */
  public function testRunJobs(CiJobEnum $job): void {
    $options = $this->createCiRunOptions([
      'job' => $job->getValue(),
      'phase' => $this->validPhaseName(),
      'sut' => $this->validSutName(),
    ]);
    $spy = $this->prophesize(TestSpy::class);
    $spy
      ->call(Argument::any())
      ->shouldBeCalledOnce();
    $spy
      ->call($options)
      ->shouldBeCalledOnce();
    $job = new CiTestJob($spy->reveal());

    $job->run($options);
  }

  /**
   * @dataProvider providerPhases
   */
  public function testRunPhases(CiJobPhaseEnum $phase): void {
    $options = $this->createCiRunOptions([
      'job' => $this->validJobName(),
      'phase' => $phase->getValue(),
      'sut' => $this->validSutName(),
    ]);
    $spy = $this->prophesize(TestSpy::class);
    $spy
      ->call($options)
      ->shouldBeCalledOnce();
    $job = new CiTestJob($spy->reveal());

    $job->run($options);
  }

  public function testExitEarly(): void {
    $options = $this->createValidRunOptions();
    $spy = $this->prophesize(TestSpy::class);
    $spy
      ->call($options)
      ->shouldNotBeCalled();
    $job = new class($spy->reveal()) extends CiTestJob {

      public function exitEarly(): bool {
        return TRUE;
      }

    };

    $job->run($options);
  }

  public function testMatchingCoreVersionExistsWithoutDrupalCoreVersion(): void {
    $job = new class(new TestSpy()) extends CiTestJob {

      public function jobName(): CiJobEnum {
        // Use the static code analysis job because it doesn't specify a Drupal
        // core version.
        return CiJobEnum::STATIC_CODE_ANALYSIS();
      }

      // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
      public function matchingCoreVersionExists(DrupalCoreVersionResolver $drupal_core_version_resolver, OutputInterface $output): bool {
        return parent::matchingCoreVersionExists($drupal_core_version_resolver, $output);
      }

    };
    $drupal_core_version_resolver = $this->prophesize(DrupalCoreVersionResolver::class);
    $this->expectException(\LogicException::class);

    $job->matchingCoreVersionExists($drupal_core_version_resolver->reveal(), new NullOutput());
  }

  /**
   * @dataProvider providerRunOrcaQaAutomatedTestsWithInstallProfile
   */
  public function testRunOrcaQaAutomatedTestsWithInstallProfile($profile, $command): void {
    $job = new class() extends AbstractCiJob {

      public function jobName(): CiJobEnum {
        return CiJobEnum::STATIC_CODE_ANALYSIS();
      }

      // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
      public function runOrcaQaAutomatedTests(array $command, EnvFacade $env_facade, ProcessRunner $process_runner): void {
        parent::runOrcaQaAutomatedTests($command, $env_facade, $process_runner);
      }

    };
    $env_facade = $this->prophesize(EnvFacade::class);
    $env_facade
      ->get('ORCA_FIXTURE_PROFILE')
      ->willReturn($profile);
    $env_facade = $env_facade->reveal();
    $process_runner = $this->prophesize(ProcessRunner::class);
    $process_runner
      ->runOrca(Argument::cetera())
      ->willReturn(0);
    $process_runner
      ->runOrca($command)
      ->shouldBeCalledOnce();
    $process_runner = $process_runner->reveal();

    $job->runOrcaQaAutomatedTests([], $env_facade, $process_runner);
  }

  public static function providerRunOrcaQaAutomatedTestsWithInstallProfile(): array {
    return [
      [
        'profile' => NULL,
        'command' => [
          'qa:automated-tests',
        ],
      ],
      [
        'profile' => 'arbitrary_profile',
        'command' => [
          'qa:automated-tests',
          '--sut-only',
        ],
      ],
      [
        'profile' => FixtureCreator::DEFAULT_PROFILE,
        'command' => [
          'qa:automated-tests',
        ],
      ],
    ];
  }

}
