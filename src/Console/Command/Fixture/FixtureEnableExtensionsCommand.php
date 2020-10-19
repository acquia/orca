<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Domain\Fixture\CompanyExtensionEnabler;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Exception;
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
   * @var \Acquia\Orca\Domain\Fixture\CompanyExtensionEnabler
   */
  private $companyExtensionEnabler;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Fixture\CompanyExtensionEnabler $company_extension_enabler
   *   The company extension enabler.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   */
  public function __construct(CompanyExtensionEnabler $company_extension_enabler, FixturePathHandler $fixture_path_handler) {
    $this->companyExtensionEnabler = $company_extension_enabler;
    $this->fixture = $fixture_path_handler;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
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
      return StatusCodeEnum::ERROR;
    }

    try {
      $this->companyExtensionEnabler->enable();
    }
    catch (Exception $e) {
      $io = new SymfonyStyle($input, $output);
      $io->error($e->getMessage());
      return StatusCodeEnum::ERROR;
    }

    return StatusCodeEnum::OK;
  }

}
