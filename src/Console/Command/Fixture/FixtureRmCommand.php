<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Fixture\FixtureRemover;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides a command.
 */
class FixtureRmCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:rm';

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The fixture remover.
   *
   * @var \Acquia\Orca\Fixture\FixtureRemover
   */
  private $fixtureRemover;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Fixture\FixtureRemover $fixture_remover
   *   The fixture remover.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, FixtureRemover $fixture_remover) {
    $this->fixture = $fixture_path_handler;
    $this->fixtureRemover = $fixture_remover;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['rm'])
      ->setDescription('Removes the test fixture')
      ->setHelp('Removes the entire site build directory and Drupal database.')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Remove without confirmation');
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
    $question = new ConfirmationQuestion(sprintf('Are you sure you want to remove the test fixture at %s? ', $this->fixture->getPath()));
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCode::USER_CANCEL;
    }

    $this->fixtureRemover->remove();
    return StatusCode::OK;
  }

}
