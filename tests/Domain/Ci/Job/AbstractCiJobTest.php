<?php

namespace Acquia\Orca\Tests\Domain\Ci\Job;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Tests\_Helper\TestSpy;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiJobTestBase;
use Acquia\Orca\Tests\Domain\Ci\Job\_Helper\CiTestJob;
use LogicException;
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

      protected function exitEarly(): bool {
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
    $this->expectException(LogicException::class);

    $job->matchingCoreVersionExists($drupal_core_version_resolver->reveal(), new NullOutput());
  }

}
