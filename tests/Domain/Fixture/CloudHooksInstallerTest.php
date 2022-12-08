<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Fixture\CloudHooksInstaller;
use Acquia\Orca\Domain\Git\GitFacade;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;

/**
 * @property \Acquia\Orca\Domain\Git\GitFacade|\Prophecy\Prophecy\ObjectProphecy $git
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 */
class CloudHooksInstallerTest extends TestCase {

  private const FIXTURE_DIR = '/var/www/orca-build';

  protected function setUp(): void {
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixture
      ->getPath()
      ->willReturn(self::FIXTURE_DIR);
    $this->git = $this->prophesize(GitFacade::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->processRunner
      ->run(Argument::any())
      ->willReturn(StatusCodeEnum::OK);
    $this->processRunner
      ->runExecutable(Argument::any(), Argument::any())
      ->willReturn(StatusCodeEnum::OK);
  }

  private function createCloudHooksInstaller(): CloudHooksInstaller {
    $fixture_path_handler = $this->fixture->reveal();
    $git = $this->git->reveal();
    $process_runner = $this->processRunner->reveal();
    return new CloudHooksInstaller($fixture_path_handler, $git, $process_runner);
  }

  public function testInstall(): void {
    $tarball = 'hooks.tar.gz';
    $this->processRunner
      ->runExecutable('curl', [
        '-f',
        '-L',
        '-o',
        $tarball,
        'https://github.com/acquia/cloud-hooks/tarball/master',
      ])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('tar', [
        'xzf',
        $tarball,
      ])
      ->shouldBeCalledOnce();
    $this->processRunner
      ->runExecutable('rm', [
        $tarball,
      ])
      ->shouldBeCalledOnce();
    $this->git
      ->commitCodeChanges(Argument::any())
      ->shouldBeCalledOnce();
    $installer = $this->createCloudHooksInstaller();

    $installer->install();
  }

}
