<?php

namespace Acquia\Orca\Console\Command\Qa;

use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Domain\Tool\DrupalCheckTool;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class QaDeprecatedCodeScanCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'qa:deprecated-code-scan';

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The "contrib" command line option.
   *
   * @var string|string[]|bool|null
   */
  private $contrib;

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Domain\Package\PackageManager
   */
  private $packageManager;

  /**
   * The drupal-check tool.
   *
   * @var \Acquia\Orca\Domain\Tool\DrupalCheckTool
   */
  private $drupalCheck;

  /**
   * The "sut" command line option.
   *
   * @var string|string[]|bool|null
   */
  private $sut;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Tool\DrupalCheckTool $drupal_check
   *   The drupal-check tool.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Package\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(DrupalCheckTool $drupal_check, FixturePathHandler $fixture_path_handler, PackageManager $package_manager) {
    $this->fixture = $fixture_path_handler;
    $this->packageManager = $package_manager;
    $this->drupalCheck = $drupal_check;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases([
        'deprecations',
        'drupal-check',
        'phpstan',
      ])
      ->setDescription('Scans for deprecated code')
      ->addOption('sut', NULL, InputOption::VALUE_REQUIRED, 'Scan the system under test (SUT). Provide its package name, e.g., "drupal/example"')
      ->addOption('contrib', NULL, InputOption::VALUE_NONE, 'Scan contributed projects');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $this->sut = $input->getOption('sut');
    $this->contrib = $input->getOption('contrib');

    if (!$this->isValidInput($output)) {
      return StatusCodeEnum::ERROR;
    }

    if (!$this->fixture->exists()) {
      $output->writeln([
        "Error: No fixture exists at {$this->fixture->getPath()}.",
        'Hint: Use the "fixture:init" command to create one.',
      ]);
      return StatusCodeEnum::ERROR;
    }

    return $this->drupalCheck->run($this->sut, $this->contrib);
  }

  /**
   * Determines whether the command input is valid.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output decorator.
   *
   * @return bool
   *   TRUE if the command input is valid or FALSE if not.
   */
  private function isValidInput(OutputInterface $output): bool {
    if (!$this->sut && !$this->contrib) {
      $output->writeln([
        'Error: Nothing to do.',
        'Hint: Use the "--sut" and "--contrib" options to specify what to scan.',
      ]);
      return FALSE;
    }

    if ($this->sut && !$this->packageManager->exists($this->sut)) {
      $output->writeln(sprintf('Error: Invalid value for "--sut" option: "%s".', $this->sut));
      return FALSE;
    }

    return TRUE;
  }

}
