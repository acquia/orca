<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Fixture\Fixture;
use Acquia\Orca\Fixture\ProjectManager;
use Acquia\Orca\Fixture\SubmoduleManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Finder\Finder $finder
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\Fixture $fixture
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Fixture\ProjectManager $projectManager
 * @property \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\Filesystem\Filesystem $filesystem
 */
class SubmoduleManagerTest extends TestCase {

  protected function setUp() {
    $this->filesystem = $this->prophesize(Filesystem::class);
    $this->finder = $this->prophesize(Finder::class);
    $this->fixture = $this->prophesize(Fixture::class);
    $this->projectManager = $this->prophesize(ProjectManager::class);
    $this->projectManager
      ->getMultiple()
      ->willReturn([]);
  }

  public function testConstruction() {
    /** @var \Symfony\Component\Filesystem\Filesystem $filesystem */
    $filesystem = $this->filesystem->reveal();
    /** @var \Symfony\Component\Finder\Finder $finder */
    $finder = $this->finder->reveal();
    /** @var \Acquia\Orca\Fixture\Fixture $fixture */
    $fixture = $this->fixture->reveal();
    /** @var \Acquia\Orca\Fixture\ProjectManager $project_manager */
    $project_manager = $this->projectManager->reveal();
    $object = new SubmoduleManager($filesystem, $finder, $fixture, $project_manager);

    $this->assertInstanceOf(SubmoduleManager::class, $object, 'Instantiated class.');
  }

}
