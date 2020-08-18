<?php

namespace Acquia\Orca\Tests\Fixture;

use Acquia\Orca\Composer\Composer;
use Acquia\Orca\Fixture\CodebaseCreator;
use Acquia\Orca\Git\Git;
use PHPUnit\Framework\TestCase;

/**
 * @property \Acquia\Orca\Composer\Composer|\Prophecy\Prophecy\ObjectProphecy $composer
 * @property \Acquia\Orca\Git\Git|\Prophecy\Prophecy\ObjectProphecy $git
 */
class CodebaseCreatorTest extends TestCase {

  protected function setUp(): void {
    $this->composer = $this->prophesize(Composer::class);
    $this->git = $this->prophesize(Git::class);
  }

  private function createCodebaseCreator(): CodebaseCreator {
    $composer = $this->composer->reveal();
    $git = $this->git->reveal();
    return new CodebaseCreator($composer, $git);
  }

  /**
   * @dataProvider providerCreate
   */
  public function testCreate(string $project_template_string, string $stability, string $directory): void {
    $this->composer
      ->createProject($project_template_string, $stability, $directory)
      ->shouldBeCalledOnce();
    $this->git
      ->ensureFixtureRepo()
      ->shouldBeCalledOnce();

    $creator = $this->createCodebaseCreator();
    $creator->create($project_template_string, $stability, $directory);
  }

  public function providerCreate(): array {
    return [
      ['test/example-project1', 'alpha', '/var/www/orca-build1'],
      ['test/example-project2', 'dev', '/var/www/orca-build2'],
    ];
  }

}
