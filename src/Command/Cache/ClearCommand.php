<?php

namespace Acquia\Orca\Command\Cache;

use Acquia\Orca\Command\StatusCodes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Clears the Symfony cache.
 *
 * @property string $cacheDir
 * @property \Symfony\Component\Filesystem\Filesystem $filesystem
 */
class ClearCommand extends Command {

  protected static $defaultName = 'cache:clear';

  /**
   * Constructs an instance.
   *
   * @param string $cache_dir
   *   The Symfony cache directory.
   * @param \Symfony\Component\Filesystem\Filesystem $filesystem
   *   The filesystem.
   */
  public function __construct(string $cache_dir, Filesystem $filesystem) {
    $this->cacheDir = $cache_dir;
    $this->filesystem = $filesystem;
    parent::__construct(self::$defaultName);
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setAliases(['cc'])
      ->setDescription('Clears the Symfony cache')
      ->setHidden(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->filesystem->remove($this->cacheDir);
    return StatusCodes::OK;
  }

}
