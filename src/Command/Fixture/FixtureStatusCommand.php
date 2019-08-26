<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureInspector;
use Acquia\Orca\Utility\StatusTable;
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
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
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
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Fixture\FixtureInspector $fixture_inspector
   *   The fixture inspector.
   */
  public function __construct(Fixture $fixture, FixtureInspector $fixture_inspector) {
    $this->fixture = $fixture;
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
      return StatusCodes::ERROR;
    }

    (new StatusTable($output))
      ->setRows($this->fixtureInspector->getOverview())
      ->render();

    return StatusCodes::OK;
  }

}
