<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides a command.
 *
 * @property \Acquia\Orca\Fixture $fixture
 */
class DestroyCommand extends Command {

  protected static $defaultName = 'fixture:destroy';

  /**
   * {@inheritdoc}
   */
  public function __construct(Fixture $fixture) {
    $this->fixture = $fixture;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['destroy'])
      ->setDescription('Destroys the test fixture')
      ->setHelp('Deletes the entire site build directory and Drupal database.')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Destroy without confirmation');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln("Error: No fixture exists at {$this->fixture->rootPath()}.");
      return StatusCodes::ERROR;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('Are you sure you want to destroy the test fixture? ');
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCodes::USER_CANCEL;
    }

    $this->fixture->destroy();
    return StatusCodes::OK;
  }

}
