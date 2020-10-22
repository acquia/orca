<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker;
use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The integrated test on oldest supported CI job.
 */
class IntegratedTestOnOldestSupportedCiJob extends AbstractCiJob {

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
   * The redundant job checker.
   *
   * @var \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker
   */
  private $redundantJobChecker;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   * @param \Acquia\Orca\Domain\Ci\Job\Helper\RedundantJobChecker $redundant_job_checker
   *   The redundant job checker.
   */
  public function __construct(OutputInterface $output, ProcessRunner $process_runner, RedundantJobChecker $redundant_job_checker) {
    $this->output = $output;
    $this->processRunner = $process_runner;
    $this->redundantJobChecker = $redundant_job_checker;
  }

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_TEST_ON_OLDEST_SUPPORTED();
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
    $this->processRunner
      ->runOrca([
        'fixture:init',
        '--force',
        "--sut={$options->getSut()->getPackageName()}",
        '--core=OLDEST_SUPPORTED',
      ]);
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
      ]);
  }

}
