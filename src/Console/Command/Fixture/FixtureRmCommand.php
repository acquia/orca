<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Domain\Fixture\FixtureRemover;
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
class FixtureRmCommand extends Command {

  /**
   * {@inheritdoc}
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
   * @var \Acquia\Orca\Domain\Fixture\FixtureRemover
   */
  private $fixtureRemover;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Fixture\FixtureRemover $fixture_remover
   *   The fixture remover.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, FixtureRemover $fixture_remover) {
    $this->fixture = $fixture_path_handler;
    $this->fixtureRemover = $fixture_remover;
    parent::__construct();
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
      return StatusCodeEnum::ERROR;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(sprintf('Are you sure you want to remove the test fixture at %s? ', $this->fixture->getPath()));
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCodeEnum::USER_CANCEL;
    }

    $this->fixtureRemover->remove();
    return StatusCodeEnum::OK;
  }

}
