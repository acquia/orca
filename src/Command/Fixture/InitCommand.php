<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Exception\OrcaException;
use Acquia\Orca\Fixture\Creator;
use Acquia\Orca\Fixture\ProjectManager;
use Acquia\Orca\Fixture\Remover;
use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 *
 * @property \Acquia\Orca\Fixture\Creator $creator
 * @property \Acquia\Orca\Fixture\Fixture $fixture
 * @property \Acquia\Orca\Fixture\ProjectManager $projectManager
 * @property \Acquia\Orca\Fixture\Remover $remover
 */
class InitCommand extends Command {

  protected static $defaultName = 'fixture:init';

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Creator $creator
   *   The fixture creator.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\ProjectManager $project_manager
   *   The project manager.
   * @param \Acquia\Orca\Fixture\Remover $remover
   *   The fixture remover.
   */
  public function __construct(Creator $creator, Fixture $fixture, ProjectManager $project_manager, Remover $remover) {
    $this->creator = $creator;
    $this->fixture = $fixture;
    $this->projectManager = $project_manager;
    $this->remover = $remover;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['init'])
      ->setDescription('Creates the test fixture')
      ->setHelp('Creates a BLT-based Drupal site build, includes the system under test using Composer, optionally includes all other Acquia product modules, and installs Drupal.')
      ->addOption('sut', NULL, InputOption::VALUE_REQUIRED, 'The system under test (SUT) in the form of its package name, e.g., "drupal/example"')
      ->addOption('sut-only', NULL, InputOption::VALUE_NONE, 'Add only the system under test (SUT). Omit all other non-required Acquia product modules')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If the fixture already exists, remove it first without confirmation');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $sut = $input->getOption('sut');
    $sut_only = $input->getOption('sut-only');

    if (!$this->isValidInput($sut, $sut_only, $output)) {
      return StatusCodes::ERROR;
    }

    if ($this->fixture->exists()) {
      if (!$input->getOption('force')) {
        $output->writeln([
          "Error: Fixture already exists at {$this->fixture->rootPath()}.",
          'Hint: Use the "--force" option to remove it and proceed.',
        ]);
        return StatusCodes::ERROR;
      }

      $this->remover->remove();
    }

    $this->setSut($sut);
    $this->setSutOnly($sut_only);

    try {
      $this->creator->create();
    }
    catch (OrcaException $e) {
      return StatusCodes::ERROR;
    }

    return StatusCodes::OK;
  }

  /**
   * Determines whether the command input is valid.
   *
   * @param string|string[]|bool|null $sut
   *   The "sut" option value.
   * @param string|string[]|bool|null $sut_only
   *   The "sut-only" option value.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   */
  private function isValidInput($sut, $sut_only, OutputInterface $output): bool {
    if ($sut_only && !$sut) {
      $output->writeln([
        'Error: Cannot create a SUT-only fixture without a SUT.',
        'Hint: Use the "--sut" option to specify the SUT.',
      ]);
      return FALSE;
    }

    if ($sut && !$this->projectManager->exists($sut)) {
      $output->writeln(sprintf('Error: Invalid value for "--sut" option: "%s".', $sut));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sets the SUT.
   *
   * @param string|string[]|bool|null $sut
   *   The SUT.
   */
  private function setSut($sut): void {
    if ($sut) {
      $this->creator->setSut($sut);
    }
  }

  /**
   * Sets the SUT-only flag.
   *
   * @param string|string[]|bool|null $sut_only
   *   The SUT-only flag.
   */
  private function setSutOnly($sut_only): void {
    if ($sut_only) {
      $this->creator->setSutOnly(TRUE);
    }
  }

}
