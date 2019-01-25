<?php

namespace Acquia\Orca\Server;

use Acquia\Orca\Fixture\Fixture;
use Cocur\BackgroundProcess\BackgroundProcess;

/**
 * Provides a ChromeDriver server.
 */
class ChromeDriverServer implements ServerInterface {

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * The process.
   *
   * @var \Cocur\BackgroundProcess\BackgroundProcess
   */
  private $process;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   */
  public function __construct(Fixture $fixture) {
    $this->fixture = $fixture;
  }

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    // ChromeDriver can't use Symfony Process because leads to timeouts during
    // test runs.
    $command = [
      $this->fixture->getPath('vendor/bin/drush'),
      "--root={$this->fixture->getPath()}",
      'runserver',
      Fixture::WEB_ADDRESS,
    ];
    $this->process = new BackgroundProcess(implode(' ', $command));
    $this->process->run();
  }

  /**
   * {@inheritdoc}
   */
  public function stop(): void {
    $this->process->stop();
  }

  /**
   * {@inheritdoc}
   */
  public function wait(): void {
    // BackgroundProcess has no "wait" feature.
  }

}
