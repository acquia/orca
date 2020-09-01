<?php

namespace Acquia\Orca\Console\Command\Ci;

use Acquia\Orca\Enum\StatusCodeEnum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class CiRunCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'ci:run';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['run'])
      ->setDescription('Runs an ORCA CI job phase')
      ->addArgument('job', InputArgument::REQUIRED, 'The job name')
      ->addArgument('phase', InputArgument::REQUIRED, 'The phase name');
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    return StatusCodeEnum::OK;
  }

}
