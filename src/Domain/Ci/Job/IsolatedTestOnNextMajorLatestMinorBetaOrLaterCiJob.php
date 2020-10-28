<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The isolated test on next major, latest minor beta-or-later CI job.
 */
class IsolatedTestOnNextMajorLatestMinorBetaOrLaterCiJob extends AbstractCiJob {

  /**
   * The Drupal core version resolver.
   *
   * @var \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver
   */
  private $drupalCoreVersionResolver;

  /**
   * The ENV facade.
   *
   * @var \Acquia\Orca\Helper\EnvFacade
   */
  private $envFacade;

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  private $output;

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\DrupalCoreVersionResolver $drupal_core_version_resolver
   *   The Drupal core version resolver.
   * @param \Acquia\Orca\Helper\EnvFacade $env_facade
   *   The ENV facade.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(DrupalCoreVersionResolver $drupal_core_version_resolver, EnvFacade $env_facade, OutputInterface $output, ProcessRunner $process_runner) {
    $this->drupalCoreVersionResolver = $drupal_core_version_resolver;
    $this->envFacade = $env_facade;
    $this->output = $output;
    $this->processRunner = $process_runner;
  }

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::ISOLATED_TEST_ON_NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER();
  }

  /**
   * {@inheritdoc}
   */
  protected function exitEarly(): bool {
    return !$this->matchingCoreVersionExists($this->drupalCoreVersionResolver, $this->output);
  }

  /**
   * {@inheritdoc}
   */
  protected function install(CiRunOptions $options): void {
    $this->runOrcaFixtureInit([
      '--force',
      "--sut={$options->getSut()->getPackageName()}",
      '--sut-only',
      '--core=NEXT_MAJOR_LATEST_MINOR_BETA_OR_LATER',
    ], $this->envFacade, $this->processRunner);
  }

  /**
   * {@inheritdoc}
   */
  protected function script(CiRunOptions $options): void {
    $this->processRunner
      ->runOrca(['fixture:status']);

    $sut = $options->getSut();
    $this->processRunner
      ->runOrca([
        'qa:automated-tests',
        "--sut={$sut->getPackageName()}",
        '--sut-only',
      ]);
  }

}
