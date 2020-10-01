<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Options\CiRunOptions;

/**
 * The isolated upgrade to next major beta-or-later CI job.
 */
class IsolatedUpgradeToNextMajorBetaOrLaterCiJob extends AbstractCiJob {

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
    return CiJobEnum::ISOLATED_UPGRADE_TO_NEXT_MAJOR_BETA_OR_LATER();
  }

  /**
   * {@inheritdoc}
   */
  protected function install(CiRunOptions $options): void {
    $this->processRunner
      ->runOrca([
        'fixture:init',
        "--sut={$options->getSut()->getPackageName()}",
      ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function script(CiRunOptions $options): void {
    $sut = $options->getSut();
    $this->processRunner
      ->runOrca([
        'qa:automated-tests',
        "--sut={$sut->getPackageName()}",
        '--sut-only',
      ]);
  }

}
