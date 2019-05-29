<?php

namespace Acquia\Orca\Command\Debug;

use Acquia\Orca\Command\StatusCodes;
use Acquia\Orca\Fixture\PackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides a command.
 */
class DebugPackagesCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'debug:packages';

  /**
   * The package manager.
   *
   * @var \Acquia\Orca\Fixture\PackageManager
   */
  private $packageManager;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Fixture\PackageManager $package_manager
   *   The package manager.
   */
  public function __construct(PackageManager $package_manager) {
    $this->packageManager = $package_manager;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['packages'])
      ->setDescription('Displays the active packages configuration');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    (new Table($output))
      ->setHeaders($this->getHeaders())
      ->setRows($this->getRows())
      ->render();
    return StatusCodes::OK;
  }

  /**
   * Gets the table headers.
   *
   * @return string[]
   *   An array of headers.
   */
  private function getHeaders(): array {
    return [
      'Package',
      'type',
      'install_path',
      'url',
      'version',
      'version_dev',
      'enable',
    ];
  }

  /**
   * Gets the table rows.
   *
   * @return string[]
   *   An array of table rows.
   */
  private function getRows(): array {
    $rows = [];
    foreach ($this->packageManager->getMultiple() as $package) {
      $rows[] = [
        $package->getPackageName(),
        $package->getType(),
        $package->getInstallPathRelative(),
        $package->getRepositoryUrl(),
        $package->getVersionRecommended(),
        $package->getVersionDev(),
        $package->shouldGetEnabled() ? 'yes' : '',
      ];
    }
    return $rows;
  }

}
