<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Server\WebServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Provides a command.
 */
class RunServerCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:run-server';

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
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
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Server\WebServer $web_server
   *   The web server.
   */
  public function __construct(Fixture $fixture, WebServer $web_server) {
    $this->fixture = $fixture;
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
      return StatusCodes::ERROR;
    }

    $output->writeln([
      'Web server started.',
      sprintf('Listening on http://%s.', Fixture::WEB_ADDRESS),
      sprintf('Document root is %s.', $this->fixture->getPath('docroot')),
      'Press ENTER to quit.',
    ]);
    $this->webServer->start();

    // Wait for user to press a key to quit.
    /** @var \Symfony\Component\Console\Helper\QuestionHelper $question */
    $question = $this->getHelper('question');
    $question->ask($input, $output, new Question(''));

    $this->webServer->stop();

    return StatusCodes::OK;
  }

}
