<?php

namespace Acquia\Orca\Tests\Domain\Fixture;

use Acquia\Orca\Domain\Fixture\FixtureCustomizer;
use Acquia\Orca\Domain\Package\Package;
use Acquia\Orca\Helper\Filesystem\FinderFactory;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Acquia\Orca\Options\FixtureOptions;
use Acquia\Orca\Tests\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FixtureCustomizerTest extends TestCase {

  /**
   * @var \Acquia\Orca\Helper\Filesystem\FinderFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  private FinderFactory|ObjectProphecy $finderFactory;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem|\Prophecy\Prophecy\ObjectProphecy
   */
  private Filesystem|ObjectProphecy $filesystem;

  /**
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler|\Prophecy\Prophecy\ObjectProphecy
   */
  private FixturePathHandler|ObjectProphecy $fixturePathHandler;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private OutputInterface|ObjectProphecy $output;

  /**
   * @var \Acquia\Orca\Options\FixtureOptions|\Prophecy\Prophecy\ObjectProphecy
   */
  private ObjectProphecy|FixtureOptions $fixtureOptions;

  private const SUT_IS_PERZ = 'drupal/acquia_perz';
  private const SUT_IS_NOT_PERZ = 'drupal/example';

  /**
   * @var \Acquia\Orca\Domain\Package\Package|\Prophecy\Prophecy\ObjectProphecy
   */
  private ObjectProphecy|Package $package;

  public function setUp(): void {
    $finder = $this->prophesize(Finder::class);
    $finder
      ->in(Argument::any())
      ->willReturn($finder);
    $finder
      ->contains(Argument::any())
      ->willReturn($finder);

    $file = $this->prophesize(SplFileInfo::class);
    $file->__toString()
      ->willReturn(Argument::type('string'));
    $fileIterator = new \ArrayIterator([$file->reveal()]);

    $finder
      ->getIterator()
      ->willReturn($fileIterator);

    $this->finderFactory = $this->prophesize(FinderFactory::class);
    $this->finderFactory
      ->create()
      ->willReturn($finder);

    $this->fixturePathHandler = $this->prophesize(FixturePathHandler::class);
    $this->fixturePathHandler
      ->getPath(Argument::type('string'))
      ->willReturn(Argument::type('string'));

    $this->output = $this->prophesize(OutputInterface::class);

  }

  public function createCustomizer(): FixtureCustomizer {
    $finderFactory = $this->finderFactory->reveal();
    $fileSystem = $this->filesystem->reveal();
    $fixturePathHandler = $this->fixturePathHandler->reveal();
    $output = $this->output->reveal();
    return new FixtureCustomizer($finderFactory, $fileSystem, $fixturePathHandler, $output);
  }

  public function testParagraphsRemovalWhenSutIsNotPerz(): void {

    $this->package = $this->prophesize(Package::class);
    $this->package
      ->getPackageName()
      ->willReturn(self::SUT_IS_NOT_PERZ);
    $this->fixtureOptions = $this->prophesize(FixtureOptions::class);
    $this->fixtureOptions
      ->getSut()
      ->willReturn($this->package);

    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->remove(Argument::any())
      ->shouldBeCalled();

    $customizer = $this->createCustomizer();
    $options = $this->fixtureOptions->reveal();
    $customizer->runCustomisations($options);

  }

  public function testParagraphsRemovalWhenSutIsPerz(): void {
    $this->package = $this->prophesize(Package::class);
    $this->package
      ->getPackageName()
      ->willReturn(self::SUT_IS_PERZ);
    $this->fixtureOptions = $this->prophesize(FixtureOptions::class);
    $this->fixtureOptions
      ->getSut()
      ->willReturn($this->package);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->remove(Argument::any())
      ->shouldNotBeCalled();
    $customizer = $this->createCustomizer();
    $options = $this->fixtureOptions->reveal();
    $customizer->runCustomisations($options);
  }

  public function testPerzParagraphsRemovalWhenSutIsNull(): void {
    $this->fixtureOptions = $this->prophesize(FixtureOptions::class);
    $this->fixtureOptions
      ->getSut()
      ->willReturn(NULL);
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->filesystem
      ->remove(Argument::any())
      ->shouldBeCalled();
    $customizer = $this->createCustomizer();
    $options = $this->fixtureOptions->reveal();
    $customizer->runCustomisations($options);
  }

}
