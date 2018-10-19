<?php

namespace Acquia\Orca\Command\Tests;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Facade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 *
 * @property \Acquia\Orca\Fixture\Facade $facade
 */
class RunCommand extends Command {

  protected static $defaultName = 'tests:run';

  /**
   * {@inheritdoc}
   */
  public function __construct(Facade $facade) {
    $this->facade = $facade;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['test'])
      ->setDescription('Runs automated tests');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $this->facade->getTester()->test();
    return StatusCodes::OK;
  }

}
