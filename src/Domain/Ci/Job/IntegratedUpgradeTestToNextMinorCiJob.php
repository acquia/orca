<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Options\CiRunOptions;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The integrated upgrade test to next minor CI job.
 */
class IntegratedUpgradeTestToNextMinorCiJob extends AbstractCiJob {

  /**
   * The output decorator.
   *
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  private $output;

  /**
   * Constructs an instance.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   */
  public function __construct(OutputInterface $output) {
    $this->output = $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function jobName(): CiJobEnum {
    return CiJobEnum::INTEGRATED_UPGRADE_TEST_TO_NEXT_MINOR();
  }

  /**
   * {@inheritdoc}
   */
  protected function install(CiRunOptions $options): void {
    $this->output->writeln(sprintf('The %s job has not yet been implemented. Skipping.', $this->jobName()));
  }

  /**
   * {@inheritdoc}
   */
  protected function script(CiRunOptions $options): void {
    $this->output->writeln(sprintf('The %s job has not yet been implemented. Skipping.', $this->jobName()));
  }

}
