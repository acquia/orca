<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\AcquiaModuleInstaller;
use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class FixtureInstallModulesCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:install-modules';

  /**
   * The Acquia module installer.
   *
   * @var \Acquia\Orca\Fixture\AcquiaModuleInstaller
   */
  private $acquiaModuleInstaller;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\AcquiaModuleInstaller $acquia_module_installer
   *   The Acquia module installer.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   */
  public function __construct(AcquiaModuleInstaller $acquia_module_installer, Fixture $fixture) {
    $this->acquiaModuleInstaller = $acquia_module_installer;
    $this->fixture = $fixture;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['enmods'])
      ->setDescription('Installs all Acquia modules')
      ->setHidden(TRUE);
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
      $this->acquiaModuleInstaller->install();
    }
    catch (\Exception $e) {
      return StatusCodes::ERROR;
    }

    return StatusCodes::OK;
  }

}
