<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Drupal\DrupalCoreVersionFinder;
use Acquia\Orca\Fixture\CodebaseCreator;
use Acquia\Orca\Fixture\FixtureCreator;
use Acquia\Orca\Fixture\FixtureInspector;
use Acquia\Orca\Fixture\SiteInstaller;
use Acquia\Orca\Fixture\SubextensionManager;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Helper\Filesystem\OrcaPathHandler;
use Acquia\Orca\Helper\Process\ProcessRunner;
use Acquia\Orca\Package\Package;
use Acquia\Orca\Package\PackageManager;
use Composer\Semver\VersionParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @property \Acquia\Orca\Composer\Composer|\Prophecy\Prophecy\ObjectProphecy $composer
 * @property \Acquia\Orca\Fixture\FixtureInspector|\Prophecy\Prophecy\ObjectProphecy $fixtureInspector
 * @property \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy $fixture
 * @property \Acquia\Orca\Helper\Filesystem\OrcaPathHandler|\Prophecy\Prophecy\ObjectProphecy $orca
 * @property \Acquia\Orca\Fixture\CodebaseCreator|\Prophecy\Prophecy\ObjectProphecy $codebaseCreator
 * @property \Acquia\Orca\Fixture\SiteInstaller|\Prophecy\Prophecy\ObjectProphecy $siteInstaller
 * @property \Acquia\Orca\Fixture\SubextensionManager|\Prophecy\Prophecy\ObjectProphecy $subextensionManager
 * @property \Acquia\Orca\Package\PackageManager|\Prophecy\Prophecy\ObjectProphecy $packageManager
 * @property \Acquia\Orca\Drupal\DrupalCoreVersionFinder|\Prophecy\Prophecy\ObjectProphecy $coreVersionFinder
 * @property \Acquia\Orca\Helper\Process\ProcessRunner|\Prophecy\Prophecy\ObjectProphecy $processRunner
 * @property \Composer\Semver\VersionParser|\Prophecy\Prophecy\ObjectProphecy $versionParser
 * @property \Symfony\Component\Console\Style\SymfonyStyle|\Prophecy\Prophecy\ObjectProphecy $output
 */
class FixtureCreatorTest extends TestCase {

  protected function setUp(): void {
    $this->codebaseCreator = $this->prophesize(CodebaseCreator::class);
    $this->composer = $this->prophesize(Composer::class);
    $this->coreVersionFinder = $this->prophesize(DrupalCoreVersionFinder::class);
    $this->fixture = $this->prophesize(FixturePathHandler::class);
    $this->fixtureInspector = $this->prophesize(FixtureInspector::class);
    $this->orca = $this->prophesize(OrcaPathHandler::class);
    $this->packageManager = $this->prophesize(PackageManager::class);
    $this->packageManager
      ->getBlt()
      ->willReturn(new Package([], $this->fixture->reveal(), $this->orca->reveal(), 'acquia/blt'));
    $this->processRunner = $this->prophesize(ProcessRunner::class);
    $this->siteInstaller = $this->prophesize(SiteInstaller::class);
    $this->subextensionManager = $this->prophesize(SubextensionManager::class);
    $this->output = $this->prophesize(SymfonyStyle::class);
    $this->versionParser = $this->prophesize(VersionParser::class);
  }

  private function createFixtureCreator(): FixtureCreator {
    $codebase_creator = $this->codebaseCreator->reveal();
    $composer_facade = $this->composer->reveal();
    $core_version_finder = $this->coreVersionFinder->reveal();
    $fixture = $this->fixture->reveal();
    $fixture_inspector = $this->fixtureInspector->reveal();
    $package_manager = $this->packageManager->reveal();
    $process_runner = $this->processRunner->reveal();
    $site_installer = $this->siteInstaller->reveal();
    $subextension_manager = $this->subextensionManager->reveal();
    $output = $this->output->reveal();
    $version_parser = $this->versionParser->reveal();
    return new FixtureCreator(
      $codebase_creator, $composer_facade, $core_version_finder, $fixture, $fixture_inspector, $site_installer, $output, $process_runner, $package_manager, $subextension_manager, $version_parser
    );
  }

  public function testInstantiation(): void {
    $creator = $this->createFixtureCreator();

    self::assertInstanceOf(FixtureCreator::class, $creator, 'Initialized class.');
  }

}
