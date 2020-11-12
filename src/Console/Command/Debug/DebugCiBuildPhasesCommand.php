<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Enum\CiJobPhaseEnum;
use Acquia\Orca\Enum\StatusCodeEnum;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class DebugCiBuildPhasesCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'debug:ci-phases';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['phases'])
      ->setDescription('Displays ORCA CI phases');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    (new Table($output))
      ->setHeaders($this->getHeaders())
      ->setRows($this->getRows())
      ->render();
    return StatusCodeEnum::OK;
  }

  /**
   * Gets the table headers.
   *
   * @return string[]
   *   An array of headers.
   */
  private function getHeaders(): array {
    return [
      '#',
      'Phase',
      'Description',
    ];
  }

  /**
   * Gets the table rows.
   *
   * @return array
   *   An array of table rows.
   */
  private function getRows(): array {
    $rows = [];
    $number = 1;
    foreach ($this->getDescriptions() as $name => $description) {
      $rows[] = [$number++, $name, $description];
    }
    return $rows;
  }

  /**
   * Gets the raw descriptions data.
   *
   * @return array
   *   An array of descriptions data.
   */
  protected function getDescriptions(): array {
    return CiJobPhaseEnum::descriptions();
  }

}
