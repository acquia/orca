<?php

namespace Acquia\Orca\Domain\Fixture;

use Acquia\Orca\Domain\Composer\Composer;
use Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper;
use Acquia\Orca\Domain\Git\Git;
use Acquia\Orca\Exception\FileNotFoundException;
use Acquia\Orca\Exception\ParseError;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;

/**
 * Creates the codebase component of a fixture.
 */
class CodebaseCreator {

  /**
   * The Composer facade.
   *
   * @var \Acquia\Orca\Domain\Composer\Composer
   */
  private $composer;

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The Git facade.
   *
   * @var \Acquia\Orca\Domain\Git\Git
   */
  private $git;

  /**
   * The fixture options.
   *
   * @var \Acquia\Orca\Domain\Fixture\FixtureOptions
   */
  private $options;

  /**
   * The fixture composer.json helper.
   *
   * @var \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper
   */
  private $composerJsonHelper;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Domain\Composer\Composer $composer
   *   The Composer facade.
   * @param \Acquia\Orca\Domain\Fixture\Helper\ComposerJsonHelper $composer_json_helper
   *   The fixture composer.json helper.
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Git\Git $git
   *   The Git facade.
   */
  public function __construct(Composer $composer, ComposerJsonHelper $composer_json_helper, FixturePathHandler $fixture_path_handler, Git $git) {
    $this->composer = $composer;
    $this->composerJsonHelper = $composer_json_helper;
    $this->fixture = $fixture_path_handler;
    $this->git = $git;
  }

  /**
   * Creates the codebase.
   *
   * @param \Acquia\Orca\Domain\Fixture\FixtureOptions $options
   *   The fixture options.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  public function create(FixtureOptions $options): void {
    $this->options = $options;
    $this->createProject();
    $this->git->ensureFixtureRepo();
    $this->configureComposerProject();
    $this->composerJsonHelper->writeFixtureOptions($options);
  }

  /**
   * Creates the Composer project.
   *
   * @throws \Acquia\Orca\Exception\OrcaException
   */
  private function createProject(): void {
    if (!$this->options->hasSut()) {
      $this->composer->createProject($this->options);
      return;
    }

    /* @var \Acquia\Orca\Domain\Package\Package $sut */
    $sut = $this->options->getSut();
    if (!$sut->isProjectTemplate()) {
      $this->composer->createProject($this->options);
      return;
    }

    $this->composer->createProjectFromPackage($sut);
  }

  /**
   * Configures the Composer project (i.e., composer.json).
   *
   * @throws \Acquia\Orca\Exception\FileNotFoundException
   * @throws \Acquia\Orca\Exception\ParseError
   * @throws \Acquia\Orca\Exception\FixtureNotExistsException
   */
  private function configureComposerProject(): void {
    $composer_json_path = $this->fixture->getPath('composer.json');
    try {
      $this->composerJsonHelper->writeFixtureOptions($this->options);
    }
    catch (FileNotFoundException $e) {
      throw new FileNotFoundException("No such file: {$composer_json_path}");
    }
    catch (ParseError $e) {
      throw new ParseError("Cannot parse {$composer_json_path}");
    }
  }

}
