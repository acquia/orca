<?php

namespace Acquia\Orca\Console\Command\Fixture;

use Acquia\Orca\Domain\Fixture\FixtureCreator;
use Acquia\Orca\Domain\Fixture\SiteInstaller;
use Acquia\Orca\Enum\StatusCodeEnum;
use Acquia\Orca\Helper\Filesystem\FixturePathHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Provides a command.
 */
class FixtureInstallSiteCommand extends Command {

  /**
   * The default command name.
   *
   * @var string
   */
  protected static $defaultName = 'fixture:install-site';

  /**
   * The fixture path handler.
   *
   * @var \Acquia\Orca\Helper\Filesystem\FixturePathHandler
   */
  private $fixture;

  /**
   * The site installer.
   *
   * @var \Acquia\Orca\Domain\Fixture\SiteInstaller
   */
  private $siteInstaller;

  /**
   * Constructs an instance.
   *
   * @param \Acquia\Orca\Helper\Filesystem\FixturePathHandler $fixture_path_handler
   *   The fixture path handler.
   * @param \Acquia\Orca\Domain\Fixture\SiteInstaller $site_installer
   *   The site installer.
   */
  public function __construct(FixturePathHandler $fixture_path_handler, SiteInstaller $site_installer) {
    $this->fixture = $fixture_path_handler;
    $this->siteInstaller = $site_installer;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.StaticAccess)
   */
  protected function configure() {
    $this
      ->setAliases(['si'])
      ->setDescription('Installs the site')
      ->setHelp('Installs Drupal and enables company extensions.')
      ->addOption('force', 'f', InputOption::VALUE_NONE, 'Install without confirmation')
      ->addOption('profile', NULL, InputOption::VALUE_REQUIRED, 'The Drupal installation profile to use, e.g., "minimal". ("orca" is a pseudo-profile based on "testing", with the Toolbar module enabled and Seven as the admin theme)', FixtureCreator::DEFAULT_PROFILE);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function execute(InputInterface $input, OutputInterface $output): int {
    if (!$this->fixture->exists()) {
      $output->writeln("Error: No fixture exists at {$this->fixture->getPath()}.");
      return StatusCodeEnum::ERROR;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion(sprintf('Are you sure you want to drop all tables in the database and install a fresh site at %s? ', $this->fixture->getPath()));
    if (
      !$input->getOption('force')
      && ($input->getOption('no-interaction') || !$helper->ask($input, $output, $question))
    ) {
      return StatusCodeEnum::USER_CANCEL;
    }

    $profile = $input->getOption('profile');
    $this->siteInstaller->install($profile);
    return StatusCodeEnum::OK;
  }

}
