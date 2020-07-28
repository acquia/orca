<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Fixture\CompanyExtensionEnabler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides a command.
 */
class FixtureEnableExtensionsCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:enable-extensions';

  /**
   * The company extension enabler.
   *
   * @var \Acquia\Orca\Fixture\CompanyExtensionEnabler
   */
  private $companyExtensionEnabler;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\CompanyExtensionEnabler $company_extension_enabler
   *   The company extension enabler.
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   */
  public function __construct(CompanyExtensionEnabler $company_extension_enabler, FixturePathHandler $fixture_path_handler) {
    $this->companyExtensionEnabler = $company_extension_enabler;
    $this->fixture = $fixture_path_handler;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['enexts'])
      ->setDescription('Enables all company Drupal extensions');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln("Error: No fixture exists at {$this->fixture->getPath()}.");
      return StatusCode::ERROR;
    }

    try {
      $this->companyExtensionEnabler->enable();
    }
    catch (\Exception $e) {
      $io = new SymfonyStyle($input, $output);
      $io->error($e->getMessage());
      return StatusCode::ERROR;
    }

    return StatusCode::OK;
  }

}
