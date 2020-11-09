<?php

namespace Acquia\Orca\Domain\Ci\Job;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Options\CiRunOptions;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The isolated upgrade test to next major dev CI job.
 */
class IsolatedUpgradeTestToNextMajorDevCiJob extends AbstractCiJob {

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
    return CiJobEnum::ISOLATED_UPGRADE_TEST_TO_NEXT_MAJOR_DEV();
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
