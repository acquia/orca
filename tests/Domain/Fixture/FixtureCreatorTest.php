<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Composer\ComposerFacade;
use Acquia\Orca\Domain\Composer\Version\VersionFinder;
use Acquia\Orca\Domain\Composer\Version\VersionGuesser;
use Acquia\Orca\Domain\Fixture\CloudHooksInstaller;
use Acquia\Orca\Domain\Fixture\CodebaseCreator;
use Acquia\Orca\Domain\Fixture\FixtureCreator;
use Acquia\Orca\Domain\Fixture\FixtureInspector;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Fixture\SiteInstaller;
use Acquia\Orca\Domain\Fixture\SubextensionManager;
use Acquia\Orca\Domain\Git\GitFacade;
use Acquia\Orca\Domain\Package\PackageManager;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @property \Acquia\Orca\Domain\Composer\ComposerFacade|\Prophecy\Prophecy\ObjectProphecy $composer
 * @property \Acquia\Orca\Domain\Composer\Version\VersionFinder|\Prophecy\Prophecy\ObjectProphecy $versionFinder
 * @property \Acquia\Orca\Domain\Composer\Version\VersionGuesser|\Prophecy\Prophecy\ObjectProphecy $versionGuesser
 * @property \Acquia\Orca\Domain\Fixture\CloudHooksInstaller|\Prophecy\Prophecy\ObjectProphecy $cloudHooksInstaller
 * @property \Acquia\Orca\Domain\Fixture\CodebaseCreator|\Prophecy\Prophecy\ObjectProphecy $codebaseCreator
 * @property \Acquia\Orca\Domain\Fixture\FixtureInspector|\Prophecy\Prophecy\ObjectProphecy $fixtureInspector
 * @property \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper|\Prophecy\Prophecy\ObjectProphecy $composerJsonHelper
 * @property \Acquia\Orca\Domain\Fixture\SiteInstaller|\Prophecy\Prophecy\ObjectProphecy $siteInstaller
 * @property \Acquia\Orca\Domain\Fixture\SubextensionManager|\Prophecy\Prophecy\ObjectProphecy $subextensionManager
 * @property \Acquia\Orca\Domain\Git\GitFacade|\Prophecy\Prophecy\ObjectProphecy $git
 * @property \Acquia\Orca\Domain\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @property \Symfony\Component\Console\Style\SymfonyStyle|\Prophecy\Prophecy\ObjectProphecy $output
 */
class FixtureCreatorTest extends TestCase {

  protected function setUp(): void {
    $this->cloudHooksInstaller = $this->prophesize(CloudHooksInstaller::class);
    $this->codebaseCreator = $this->prophesize(CodebaseCreator::class);
    $this->composer = $this->prophesize(ComposerFacade::class);
    $this->composerJsonHelper = $this->prophesize(ComposerJsonHelper::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixtureInspector = $this->prophesize(FixtureInspector::class);
    $this->git = $this->prophesize(GitFacade::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->siteInstaller = $this->prophesize(SiteInstaller::class);
    $this->subextensionManager = $this->prophesize(SubextensionManager::class);
    $this->versionFinder = $this->prophesize(VersionFinder::class);
    $this->versionGuesser = $this->prophesize(VersionGuesser::class);
    $this->output = $this->prophesize(SymfonyStyle::class);
  }

  private function createFixtureCreator(): FixtureCreator {
    $cloud_hooks_installer = $this->cloudHooksInstaller->reveal();
    $codebase_creator = $this->codebaseCreator->reveal();
    $composer_facade = $this->composer->reveal();
    $composer_json_helper = $this->composerJsonHelper->reveal();
    $fixture = $this->fixture->reveal();
    $fixture_inspector = $this->fixtureInspector->reveal();
    $git = $this->git->reveal();
    $package_manager = $this->packageManager->reveal();
    $process_runner = $this->processRunner->reveal();
    $site_installer = $this->siteInstaller->reveal();
    $output = $this->output->reveal();
    $subextension_manager = $this->subextensionManager->reveal();
    $version_finder = $this->versionFinder->reveal();
    $version_guesser = $this->versionGuesser->reveal();
    return new FixtureCreator($cloud_hooks_installer, $codebase_creator, $composer_facade, $composer_json_helper, $fixture, $fixture_inspector, $git, $site_installer, $output, $process_runner, $package_manager, $subextension_manager, $version_finder, $version_guesser);
  }

  public function testInstantiation(): void {
    $creator = $this->createFixtureCreator();

    self::assertInstanceOf(FixtureCreator::class, $creator, 'Initialized class.');
  }

}
