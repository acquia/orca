<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureResetter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides a command.
 */
class FixtureResetCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:reset';

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The fixture resetter.
   *
   * @var \Acquia\Orca\Fixture\FixtureResetter
   */
  private $fixtureResetter;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureResetter $fixture_backupper
   *   The fixture resetter.
   */
  public function __construct(Fixture $fixture, FixtureResetter $fixture_backupper) {
    $this->fixture = $fixture;
    $this->fixtureResetter = $fixture_backupper;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['reset'])
      ->setDescription('Resets the test fixture')
      ->setHelp('Restores the original state of the fixture, including codebase and Drupal database.')
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
    $question = new ConfirmationQuestion(sprintf('Are you sure you want to reset the test fixture at %s? ', $this->fixture->getPath()));
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCode::USER_CANCEL;
    }

    $this->fixtureResetter->reset();
    return StatusCode::OK;
  }

}
