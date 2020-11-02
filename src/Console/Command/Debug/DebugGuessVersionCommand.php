<?php

namespace Acquia\Orca\Console\Command\Debug;

use Acquia\Orca\Domain\Composer\Version\VersionGuesser;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Exception\OrcaException;
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
   * The version guesser.
   *
   * @var \Composer\Package\Version\VersionGuesser
   */
  private $versionGuesser;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Version\VersionGuesser $version_guesser
   *   The version guesser.
   */
  public function __construct(VersionGuesser $version_guesser) {
    $this->versionGuesser = $version_guesser;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setAliases(['guess'])
      ->setDescription('Gets the version Composer guesses for a given path repository')
      ->addArgument('path', InputArgument::REQUIRED, 'The path to guess the version for');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    $path = $input->getArgument('path');

    try {
      $guess = $this->versionGuesser->guessVersion($path);
    }
    catch (OrcaException $e) {
      $output->writeln("Error: {$e->getMessage()}");
      return StatusCodeEnum::ERROR;
    }

    $output->writeln($guess);
    return StatusCodeEnum::OK;
  }

}
