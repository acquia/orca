<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Drupal\DrupalCoreVersion;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Fixture\FixtureOptionsFactory;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Fixture\SutPreconditionsTester;
use Acquia\Orca\Helper\Exception\OrcaException;
use Acquia\Orca\Helper\Exception\OrcaInvalidArgumentException;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides a command.
 */
class FixtureInitCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:init';

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The fixture creator.
   *
   * @var \Acquia\Orca\Fixture\FixtureCreator
   */
  private $fixtureCreator;

  /**
   * The fixture options factory.
   *
   * @var \Acquia\Orca\Fixture\FixtureOptionsFactory
   */
  private $fixtureOptionsFactory;

  /**
   * The fixture remover.
   *
   * @var \Acquia\Orca\Fixture\FixtureRemover
   */
  private $fixtureRemover;

  /**
   * The SUT preconditions tester.
   *
   * @var \Acquia\Orca\Fixture\SutPreconditionsTester
   */
  private $sutPreconditionsTester;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\FixtureOptionsFactory $fixture_options_factory
   *   The fixture options factory.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Fixture\FixtureCreator $fixture_creator
   *   The fixture creator.
   * @param \Acquia\Orca\Fixture\FixtureRemover $fixture_remover
   *   The fixture remover.
   * @param \Acquia\Orca\Fixture\SutPreconditionsTester $sut_preconditions_tester
   *   The SUT preconditions tester.
   */
  public function __construct(FixtureOptionsFactory $fixture_options_factory, FixturePathHandler $fixture_path_handler, FixtureCreator $fixture_creator, FixtureRemover $fixture_remover, SutPreconditionsTester $sut_preconditions_tester) {
    $this->fixture = $fixture_path_handler;
    $this->fixtureCreator = $fixture_creator;
    $this->fixtureOptionsFactory = $fixture_options_factory;
    $this->fixtureRemover = $fixture_remover;
    $this->sutPreconditionsTester = $sut_preconditions_tester;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function configure(): void {
    $this
      ->setAliases(['init'])
      ->setDescription('Creates the test fixture')
      ->setHelp('Creates a Drupal site build, includes the system under test using Composer, optionally includes all other company packages, and installs Drupal.')

      // Fundamental options.
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'If the fixture already exists, remove it first without confirmation')
      ->addOption('sut', NULL, InputOption::VALUE_REQUIRED, 'The system under test (SUT) in the form of its package name, e.g., "drupal/example"')
      ->addOption('sut-only', NULL, InputOption::VALUE_NONE, 'Add only the system under test (SUT). Omit all other non-required company packages')

      // Common options.
      ->addOption('bare', NULL, InputOption::VALUE_NONE, 'Omit all non-required company packages')
      ->addOption('core', NULL, InputOption::VALUE_REQUIRED, implode(PHP_EOL, array_merge(
        ['Change the version of Drupal core installed:'],
        DrupalCoreVersion::commandHelp(),
        ['- Any version string Composer understands, see https://getcomposer.org/doc/articles/versions.md']
      )), DrupalCoreVersion::CURRENT_RECOMMENDED)
      ->addOption('dev', NULL, InputOption::VALUE_NONE, 'Use dev versions of company packages')
      ->addOption('profile', NULL, InputOption::VALUE_REQUIRED, 'The Drupal installation profile to use, e.g., "minimal". ("orca" is a pseudo-profile based on "minimal", with the Toolbar module enabled and Seven as the admin theme)', FixtureCreator::DEFAULT_PROFILE)

      // Uncommon options.
      ->addOption('project-template', NULL, InputOption::VALUE_REQUIRED, 'The Composer project template used to create the fixture', FixtureCreator::DEFAULT_PROJECT_TEMPLATE)
      ->addOption('ignore-patch-failure', NULL, InputOption::VALUE_NONE, 'Do not exit on failure to apply Composer patches. (Useful for debugging failures)')
      ->addOption('no-sqlite', NULL, InputOption::VALUE_NONE, 'Use the default database settings instead of SQLite')
      ->addOption('no-site-install', NULL, InputOption::VALUE_NONE, 'Do not install Drupal. Supersedes the "--profile" option')
      ->addOption('prefer-source', NULL, InputOption::VALUE_NONE, 'Force installation of non-company packages from sources when possible, including VCS information. (Company packages are always installed from source.) Useful for core and contrib work')
      ->addOption('symlink-all', NULL, InputOption::VALUE_NONE, 'Symlink all possible company packages via local path repository. Packages absent from the expected location will be installed normally');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    try {
      $options = $this->fixtureOptionsFactory
        ->create([
          'bare' => $input->getOption('bare'),
          'core' => $input->getOption('core'),
          'dev' => $input->getOption('dev'),
          'force' => $input->getOption('force'),
          'ignore-patch-failure' => $input->getOption('ignore-patch-failure'),
          'no-site-install' => $input->getOption('no-site-install'),
          'no-sqlite' => $input->getOption('no-sqlite'),
          'prefer-source' => $input->getOption('prefer-source'),
          'profile' => $input->getOption('profile'),
          'project-template' => $input->getOption('project-template'),
          'sut' => $input->getOption('sut'),
          'sut-only' => $input->getOption('sut-only'),
          'symlink-all' => $input->getOption('symlink-all'),
        ]);
    }
    catch (OrcaInvalidArgumentException $e) {
      $output->writeln("Error: {$e->getMessage()}");
      return StatusCode::ERROR;
    }

    try {
      $this->testPreconditions($input->getOption('sut'));
      if ($this->fixture->exists()) {
        if (!$input->getOption('force')) {
          $output->writeln([
            "Error: Fixture already exists at {$this->fixture->getPath()}.",
            'Hint: Use the "--force" option to remove it and proceed.',
          ]);
          return StatusCode::ERROR;
        }
        $this->fixtureRemover->remove();
      }
      $this->fixtureCreator->create($options);
    }
    catch (OrcaException $e) {
      (new SymfonyStyle($input, $output))
        ->error($e->getMessage());
      return StatusCode::ERROR;
    }

    return StatusCode::OK;
  }

  /**
   * Tests preconditions.
   *
   * @param string|string[]|bool|null $sut
   *   The SUT.
   *
   * @throws \Acquia\Orca\Helper\Exception\OrcaException
   *   If preconditions are not satisfied.
   */
  private function testPreconditions($sut): void {
    if ($sut) {
      $this->sutPreconditionsTester->test($sut);
    }
  }

}
