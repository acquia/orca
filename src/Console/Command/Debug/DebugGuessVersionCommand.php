<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Helper\Exception\OrcaException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class DebugGuessVersionCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'debug:guess-version';

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Composer\Composer
   */
  private $composer;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Composer\Composer $composer
   *   The Composer facade.
   */
  public function __construct(Composer $composer) {
    parent::__construct(self::$defaultName);
    $this->composer = $composer;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['guess'])
      ->setDescription('Displays ORCA environment variables')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to guess the version for');
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $path = $input->getArgument('path');

    try {
      $guess = $this->composer->guessVersion($path);
    }
    catch (OrcaException $e) {
      $output->writeln("Error: {$e->getMessage()}");
      return StatusCode::ERROR;
    }

    $output->writeln($guess);
    return StatusCode::OK;
  }

}
