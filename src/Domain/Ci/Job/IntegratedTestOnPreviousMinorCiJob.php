<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Helper\EnvFacade;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The integrated test on previous minor CI job.
 */
class IntegratedTestOnPreviousMinorCiJob extends AbstractCiJob {

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
   * The redundant job finder.
   *
   * @var \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker
   */
  private $redundantJobChecker;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\EnvFacade $env_facade
   *   The ENV facade.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker $redundant_job_checker
   *   The redundant job finder.
   */
  public function __construct(EnvFacade $env_facade, OutputInterface $output, ProcessRunner $process_runner, RedundantJobChecker $redundant_job_checker) {
    $this->envFacade = $env_facade;
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->redundantJobChecker = $redundant_job_checker;
  }

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_TEST_ON_PREVIOUS_MINOR();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Acquia\Orca\Exception\OrcaVersionNotFoundException
   */
  protected function exitEarly(): bool {
    return $this->isRedundant($this->redundantJobChecker, $this->output);
  }

  /**
   * {@inheritdoc}
   */
  protected function install(CiRunOptions $options): void {
    $this->runOrcaFixtureInit([
      '--force',
      "--sut={$options->getSut()->getPackageName()}",
      "--core={$this->getDrupalCoreVersion()}",
    ], $this->envFacade, $this->processRunner);
  }

  /**
   * {@inheritdoc}
   */
  protected function script(CiRunOptions $options): void {
    $this->processRunner
      ->runOrca(['fixture:status']);

    $this->runOrcaQaAutomatedTests([
      "--sut={$options->getSut()->getPackageName()}",
    ], $options, $this->envFacade, $this->processRunner);
  }

}
