<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Filesystem\FixturePathHandler;
use Acquia\Orca\Server\WebServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class FixtureRunServerCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:run-server';

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The web server.
   *
   * @var \Acquia\Orca\Server\WebServer
   */
  private $webServer;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Server\WebServer $web_server
   *   The web server.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, WebServer $web_server) {
    $this->fixture = $fixture_path_handler;
    $this->webServer = $web_server;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['serve'])
      ->setDescription('Runs the web server for development');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln([
        "Error: No fixture exists at {$this->fixture->getPath()}.",
        'Hint: Use the "fixture:init" command to create one.',
      ]);
      return StatusCode::ERROR;
    }

    $output->writeln('Starting web server...');
    $this->webServer->start();
    $output->writeln([
      sprintf('Listening on http://%s.', WebServer::WEB_ADDRESS),
      "Document root is {$this->fixture->getPath('docroot')}.",
      'Press Ctrl-C to quit.',
    ]);

    // Wait for SIGINT (Ctrl-C) to kill process.
    $this->webServer->wait();

    return StatusCode::OK;
  }

}
