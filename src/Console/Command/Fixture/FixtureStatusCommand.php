<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Console\Helper\StatusCode;
use Acquia\Orca\Console\Helper\StatusTable;
use Acquia\Orca\Fixture\FixtureInspector;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class FixtureStatusCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:status';

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The fixture inspector.
   *
   * @var \Acquia\Orca\Fixture\FixtureInspector
   */
  private $fixtureInspector;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Fixture\FixtureInspector $fixture_inspector
   *   The fixture inspector.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, FixtureInspector $fixture_inspector) {
    $this->fixture = $fixture_path_handler;
    $this->fixtureInspector = $fixture_inspector;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['status', 'st'])
      ->setDescription('Provides an overview of the fixture');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln("Error: No fixture exists at {$this->fixture->getPath()}.");
      return StatusCode::ERROR;
    }

    (new StatusTable($output))
      ->setRows($this->fixtureInspector->getOverview())
      ->render();

    return StatusCode::OK;
  }

}
