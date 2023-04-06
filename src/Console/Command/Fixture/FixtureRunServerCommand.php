<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Domain\Server\ProcessOutputCallback;
use Acquia\Orca\Domain\Server\WebServer;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class FixtureRunServerCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'fixture:run-server';

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The web server.
   *
   * @var \Acquia\Orca\Domain\Server\WebServer
   */
  private $webServer;

  /**
   * The callback to output process details.
   *
   * @var \Acquia\Orca\Domain\Server\ProcessOutputCallback
   */
  private $callback;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Server\ProcessOutputCallback $callback
   *   The callback to output process details.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Server\WebServer $web_server
   *   The web server.
   */
  public function __construct(ProcessOutputCallback $callback, FixturePathHandler $fixture_path_handler, WebServer $web_server) {
    $this->callback = $callback;
    $this->fixture = $fixture_path_handler;
    $this->webServer = $web_server;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
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
      return StatusCodeEnum::ERROR;
    }

    $output->writeln('Starting web server...');
    $this->webServer->start($this->callback);
    $output->writeln([
      sprintf('Listening on http://%s.', WebServer::WEB_ADDRESS),
      "Document root is {$this->fixture->getPath('docroot')}.",
      'Press Ctrl-C to quit.',
    ]);

    // Wait for SIGINT (Ctrl-C) to kill process.
    $this->webServer->wait();

    return StatusCodeEnum::OK;
  }

}
