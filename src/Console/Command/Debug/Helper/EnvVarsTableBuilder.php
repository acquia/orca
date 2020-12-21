<?php

namespace Acquia\Orca\Console\Command\Debug\Helper;

use Acquia\Orca\Enum\EnvVarEnum;
use Acquia\Orca\Helper\EnvFacade;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds an ORCA environment variables Symfony Console table.
 */
class EnvVarsTableBuilder {

  /**
   * The ENV facade.
   *
   * @var \Acquia\Orca\Helper\EnvFacade
   */
  private $envFacade;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\EnvFacade $env_facade
   *   The ENV facade.
   */
  public function __construct(EnvFacade $env_facade) {
    $this->envFacade = $env_facade;
  }

  /**
   * Builds the table.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return \Symfony\Component\Console\Helper\Table
   *   The table built.
   */
  public function build(OutputInterface $output): Table {
    return (new Table($output))
      ->setHeaders(['Variable', 'Value', 'Description'])
      ->setRows($this->getRows());
  }

  /**
   * Gets the table rows.
   *
   * @return array
   *   The table rows.
   */
  private function getRows(): array {
    $rows = [];
    foreach ($this->getVars() as $var) {
      $rows[] = [
        $var->getKey(),
        $this->envFacade->get($var->getKey()) ?: '~',
        $var->getDescription(),
      ];
    }
    return $rows;
  }

  /**
   * Gets the ENV enums.
   *
   * This method is extracted exclusively for testability.
   *
   * @return \Acquia\Orca\Enum\EnvVarEnum[]
   *   The version enums.
   *
   * @codeCoverageIgnore
   */
  protected function getVars(): array {
    return EnvVarEnum::values();
  }

}
