<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Domain\Git\Git;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
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
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The Git facade.
   *
   * @var \Acquia\Orca\Domain\Git\Git
   */
  private $git;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Git\Git $git
   *   The Git facade.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, Git $git) {
    $this->fixture = $fixture_path_handler;
    $this->git = $git;
    parent::__construct();
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
      return StatusCodeEnum::ERROR;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(sprintf('Are you sure you want to overwrite the backup of the test fixture at %s? ', $this->fixture->getPath()));
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCodeEnum::USER_CANCEL;
    }

    $this->git->backupFixtureRepo();
    return StatusCodeEnum::OK;
  }

}
