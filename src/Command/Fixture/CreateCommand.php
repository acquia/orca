<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Facade;
use Acquia\Orca\Fixture\ProductData;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 *
 * @property \Acquia\Orca\Fixture\Facade $facade
 * @property \Acquia\Orca\Fixture\ProductData $productData
 */
class CreateCommand extends Command {

  protected static $defaultName = 'fixture:create';

  /**
   * {@inheritdoc}
   */
  public function __construct(Facade $facade, ProductData $product_data) {
    $this->facade = $facade;
    $this->productData = $product_data;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['create'])
      ->setDescription('Creates the test fixture')
      ->setHelp('Creates a BLT-based Drupal site build, includes the system under test using Composer, optionally includes all other Acquia product modules, and installs Drupal.')
      ->addOption('sut', NULL, InputOption::VALUE_REQUIRED, 'The system under test (SUT) in the form of its package name, e.g., "drupal/example"')
      ->addOption('sut-only', 'o', InputOption::VALUE_NONE, 'Add only the system under test (SUT). Omit all other non-required Acquia product modules')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If the fixture already exists, destroy it first without confirmation');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $sut = $input->getOption('sut');
    $sut_only = $input->getOption('sut-only');

    if ($sut_only && !$sut) {
      $output->writeln([
        'Error: Cannot create a SUT-only fixture without a SUT.',
        'Hint: Use the "--sut" option to specify the SUT.',
      ]);
      return StatusCodes::ERROR;
    }

    if ($sut && !$this->productData->isValidPackage($sut)) {
      $output->writeln(sprintf('Error: Invalid value for "--sut" option: "%s".', $sut));
      return StatusCodes::ERROR;
    }

    if ($this->facade->exists()) {
      if (!$input->getOption('force')) {
        $output->writeln([
          "Error: Fixture already exists at {$this->facade->rootPath()}.",
          'Hint: Use the "--force" option to destroy it and proceed.',
        ]);
        return StatusCodes::ERROR;
      }

      $this->facade->getDestroyer()->destroy();
    }

    $creator = $this->facade->getCreator();

    if ($sut) {
      $creator->setSut($sut);
    }

    if ($sut_only) {
      $creator->setSutOnly(TRUE);
    }

    $creator->create();

    return StatusCodes::OK;
  }

}
