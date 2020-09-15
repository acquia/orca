<?php

namespace Acquia\Orca\Console\Command\Ci;

use Acquia\Orca\Enum\CiJobEnum;
use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Options\CiRunOptions;
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
      ->addArgument('job', InputArgument::REQUIRED, $this->getJobArgumentDescription())
      ->addArgument('phase', InputArgument::REQUIRED, $this->getPhaseArgumentDescription());
  }

  /**
   * Gets the "job" argument description.
   *
   * @return string
   *   The description text.
   */
  private function getJobArgumentDescription(): string {
    return $this->formatArgumentDescription('The job name', CiJobEnum::descriptions());
  }

  /**
   * Gets the "phase" argument description.
   *
   * @return string
   *   The description text.
   */
  private function getPhaseArgumentDescription(): string {
    return $this->formatArgumentDescription('The phase name', CiJobPhaseEnum::descriptions());
  }

  /**
   * Formats the description text for a given argument.
   *
   * @param string $summary
   *   The one-line description summary.
   * @param array $values
   *   An array of allowable argument values as keys with their descriptions as
   *   array values.
   *
   * @return string
   *   The formatted text.
   */
  private function formatArgumentDescription(string $summary, array $values): string {
    $description = ["{$summary}:"];
    foreach ($values as $key => $value) {
      $description[] = "- {$key}: {$value}";
    }
    return implode(PHP_EOL, array_merge($description));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    try {
      new CiRunOptions([
        'job' => $input->getArgument('job'),
        'phase' => $input->getArgument('phase'),
      ]);
    }
    catch (OrcaInvalidArgumentException $e) {
      $output->writeln("Error: {$e->getMessage()}");
      return StatusCodeEnum::ERROR;
    }
    return StatusCodeEnum::OK;
  }

}
