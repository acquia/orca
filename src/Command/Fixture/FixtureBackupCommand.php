<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Facade\GitFacade;
use Acquia\Orca\Filesystem\FixturePathHandler;
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
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The Git facade.
   *
   * @var \Acquia\Orca\Facade\GitFacade
   */
  private $git;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Facade\GitFacade $git
   *   The Git facade.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, GitFacade $git) {
    $this->fixture = $fixture_path_handler;
    $this->git = $git;
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
      return StatusCode::ERROR;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(sprintf('Are you sure you want to overwrite the backup of the test fixture at %s? ', $this->fixture->getPath()));
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCode::USER_CANCEL;
    }

    $this->git->backupFixtureState();
    return StatusCode::OK;
  }

}
