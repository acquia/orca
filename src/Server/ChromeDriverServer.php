<?php

namespace Acquia\Orca\Server;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Utility\ProcessRunner;
use Symfony\Component\Process\Process;

/**
 * Provides a ChromeDriver server.
 */
class ChromeDriverServer extends ServerBase {

  /**
   * The ORCA project directory.
   *
   * @var string
   */
  private $projectDir;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   * @param \Acquia\Orca\Utility\ProcessRunner $process_runner
   *   The process runner.
   * @param string $project_dir
   *   The ORCA project directory.
   */
  public function __construct(Fixture $fixture, ProcessRunner $process_runner, string $project_dir) {
    parent::__construct($fixture, $process_runner);
    $this->projectDir = $project_dir;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function createProcess(): Process {
    $command = "{$this->projectDir}/vendor/bin/chromedriver \\
      --disable-dev-shm-usage \\
      --disable-extensions \\
      --disable-gpu \\
      --headless \\
      --no-sandbox \\
      --port=4444 &";
    return Process::fromShellCommandline($command);
  }

}
