<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Codebase\CodebaseCreator;
use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\FixtureConfigurator;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Fixture\FixtureInspector;
use Acquia\Orca\Fixture\Package;
use Acquia\Orca\Fixture\PackageManager;
use Acquia\Orca\Fixture\SiteInstaller;
use Acquia\Orca\Fixture\SubextensionManager;
use Acquia\Orca\Utility\DrupalCoreVersionFinder;
use Acquia\Orca\Utility\ProcessRunner;
use Composer\Package\Version\VersionGuesser;
use Composer\Semver\VersionParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @property \Acquia\Orca\Codebase\CodebaseCreator|\Prophecy\Prophecy\ObjectProphecy $codebaseCreator
 * @property \Acquia\Orca\Fixture\FixtureConfigurator|\Prophecy\Prophecy\ObjectProphecy $fixtureConfigurator
 * @property \Acquia\Orca\Fixture\FixtureInspector|\Prophecy\Prophecy\ObjectProphecy $fixtureInspector
 * @property \Acquia\Orca\Fixture\Fixture|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Fixture\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Fixture\SiteInstaller|\Prophecy\Prophecy\ObjectProphecy $siteInstaller
 * @property \Acquia\Orca\Fixture\SubextensionManager|\Prophecy\Prophecy\ObjectProphecy $subextensionManager
 * @property \Acquia\Orca\Utility\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $coreVersionFinder
 * @property \Acquia\Orca\Utility\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @property \Composer\Package\Version\VersionGuesser|\Prophecy\Prophecy\ObjectProphecy $versionGuesser
 * @property \Composer\Semver\VersionParser|\Prophecy\Prophecy\ObjectProphecy $versionParser
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Console\Style\SymfonyStyle $symfonyStyle
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 */
class FixtureCreatorTest extends TestCase {

  protected function setUp() {
    $this->codebaseCreator = $this->prophesize(CodebaseCreator::class);
    $this->coreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->fixtureConfigurator = $this->prophesize(FixtureConfigurator::class);
    $this->fixtureInspector = $this->prophesize(FixtureInspector::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->getBlt()
      ->willReturn(new Package([], $this->fixture->reveal(), 'acquia/blt', '../blt'));
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->siteInstaller = $this->prophesize(SiteInstaller::class);
    $this->subextensionManager = $this->prophesize(SubextensionManager::class);
    $this->symfonyStyle = $this->prophesize(SymfonyStyle::class);
    $this->versionGuesser = $this->prophesize(VersionGuesser::class);
    $this->versionParser = $this->prophesize(VersionParser::class);
  }

  public function createFixtureCreator(): FixtureCreator {
    $codebase_creator = $this->codebaseCreator->reveal();
    $core_version_finder = $this->coreVersionFinder->reveal();
    $filesystem = $this->filesystem->reveal();
    $fixture = $this->fixture->reveal();
    $fixture_configurator = $this->fixtureConfigurator->reveal();
    $fixture_inspector = $this->fixtureInspector->reveal();
    $package_manager = $this->packageManager->reveal();
    $process_runner = $this->processRunner->reveal();
    $site_installer = $this->siteInstaller->reveal();
    $subextension_manager = $this->subextensionManager->reveal();
    $symfony_style = $this->symfonyStyle->reveal();
    $version_guesser = $this->versionGuesser->reveal();
    $version_parser = $this->versionParser->reveal();
    return new FixtureCreator(
      $codebase_creator,
      $core_version_finder,
      $filesystem,
      $fixture,
      $fixture_configurator,
      $fixture_inspector,
      $site_installer,
      $symfony_style,
      $process_runner,
      $package_manager,
      $subextension_manager,
      $version_guesser,
      $version_parser
    );
  }

  public function testInstantiation(): void {
    $creator = $this->createFixtureCreator();

    $this->assertInstanceOf(FixtureCreator::class, $creator, 'Initialized class.');
  }

}
