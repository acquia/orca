<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;

/**
 * The integrated test on next minor CI job.
 */
class IntegratedTestOnNextMinorCiJob extends AbstractCiJob {

  /**
   * The process runner.
   *
   * @var \Acquia\Orca\Helper\Process\ProcessRunner
   */
  private $processRunner;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Process\ProcessRunner $process_runner
   *   The process runner.
   */
  public function __construct(ProcessRunner $process_runner) {
    $this->processRunner = $process_runner;
  }

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_TEST_ON_NEXT_MINOR();
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
        '--core=NEXT_MINOR',
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
