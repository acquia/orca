<?php

namespace Acquia\Orca\Command\Fixture;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\AcquiaExtensionEnabler;
use Acquia\Orca\Fixture\Fixture;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class FixtureEnableExtensionsCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:enable-extensions';

  /**
   * The Acquia extension enabler.
   *
   * @var \Acquia\Orca\Fixture\AcquiaExtensionEnabler
   */
  private $acquiaExtensionEnabler;

  /**
   * The fixture.
   *
   * @var \Acquia\Orca\Fixture\Fixture
   */
  private $fixture;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\AcquiaExtensionEnabler $acquia_extension_enabler
   *   The Acquia extension enabler.
   * @param \Acquia\Orca\Fixture\Fixture $fixture
   *   The fixture.
   */
  public function __construct(AcquiaExtensionEnabler $acquia_extension_enabler, Fixture $fixture) {
    $this->acquiaExtensionEnabler = $acquia_extension_enabler;
    $this->fixture = $fixture;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases([
        'extensions',
        // Backward compatibility alias.
        // @todo Remove this once Lightning no longer uses it.
        'fixture:enable-modules',
      ])
      ->setDescription('Enables all Acquia Drupal extensions');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln("Error: No fixture exists at {$this->fixture->getPath()}.");
      return StatusCodes::ERROR;
    }

    try {
      $this->acquiaExtensionEnabler->enable();
    }
    catch (\Exception $e) {
      return StatusCodes::ERROR;
    }

    return StatusCodes::OK;
  }

}
