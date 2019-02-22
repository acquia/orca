<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\AcquiaModuleEnabler;
use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class FixtureEnableModulesCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:enable-modules';

  /**
   * The Acquia module enabler.
   *
   * @var \Acquia\Orca\Fixture\AcquiaModuleEnabler
   */
  private $acquiaModuleEnabler;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\AcquiaModuleEnabler $acquia_module_enabler
   *   The Acquia module enabler.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   */
  public function __construct(AcquiaModuleEnabler $acquia_module_enabler, Fixture $fixture) {
    $this->acquiaModuleEnabler = $acquia_module_enabler;
    $this->fixture = $fixture;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['enmods'])
      ->setDescription('Enables all Acquia modules');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln("Error: No fixture exists at {$this->fixture->getPath()}.");
      return StatusCodes::ERROR;
    }

    try {
      $this->acquiaModuleEnabler->enable();
    }
    catch (\Exception $e) {
      return StatusCodes::ERROR;
    }

    return StatusCodes::OK;
  }

}
