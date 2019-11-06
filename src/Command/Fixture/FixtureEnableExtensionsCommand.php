<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Fixture\CompanyExtensionEnabler;
use Acquia\Orca\Fixture\Fixture;
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
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\CompanyExtensionEnabler $company_extension_enabler
   *   The company extension enabler.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   */
  public function __construct(CompanyExtensionEnabler $company_extension_enabler, Fixture $fixture) {
    $this->companyExtensionEnabler = $company_extension_enabler;
    $this->fixture = $fixture;
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
