<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureBackupper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides a command.
 */
class FixtureBackupCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:backup';

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The fixture backupper.
   *
   * @var \Acquia\Orca\Fixture\FixtureBackupper
   */
  private $fixtureBackupper;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureBackupper $fixture_backupper
   *   The fixture backupper.
   */
  public function __construct(Fixture $fixture, FixtureBackupper $fixture_backupper) {
    $this->fixture = $fixture;
    $this->fixtureBackupper = $fixture_backupper;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['backup'])
      ->setDescription('Backs up the test fixture')
      ->setHelp('Backs up the current state of the fixture, including codebase and Drupal database.')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Backup without confirmation');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln("Error: No fixture exists at {$this->fixture->getPath()}.");
      return StatusCodes::ERROR;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(sprintf('Are you sure you want to overwrite the backup of the test fixture at %s? ', $this->fixture->getPath()));
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCodes::USER_CANCEL;
    }

    $this->fixtureBackupper->backup();
    return StatusCodes::OK;
  }

}
